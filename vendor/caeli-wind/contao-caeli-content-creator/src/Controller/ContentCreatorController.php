<?php

declare(strict_types=1);

/*
 * This file is part of Caeli KI Content-Creator.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/contao-caeli-content-creator
 */

namespace CaeliWind\ContaoCaeliContentCreator\Controller;

use CaeliWind\ContaoCaeliContentCreator\Model\CaeliContentCreatorModel;
use CaeliWind\ContaoCaeliContentCreator\Service\GrokApiService;
use CaeliWind\ContaoCaeliContentCreator\Service\NewsContentGenerator;
use CaeliWind\ContaoCaeliContentCreator\Service\DuplicateChecker;
use CaeliWind\ContaoCaeliContentCreator\Service\PromptBuilder;
use Contao\BackendTemplate;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Controller\AbstractBackendController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\Input;
use Contao\Message;
use Contao\NewsArchiveModel;
use Contao\NewsModel;
use Contao\System;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/contao/caeli_content_creator', defaults: ['_scope' => 'backend'])]
class ContentCreatorController extends AbstractBackendController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly GrokApiService $grokApiService,
        private readonly NewsContentGenerator $newsContentGenerator,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly PromptBuilder $promptBuilder,
        private readonly DuplicateChecker $duplicateChecker,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Zeigt die Vorschau für den generierten Inhalt an
     */
    #[Route('/preview/{id}', name: 'caeli_content_creator_preview')]
    public function preview(Request $request, int $id): Response
    {
        try {
            $this->framework->initialize();

            $this->logger->info('Starte Preview', ['id' => $id]);

            $inputAdapter = $this->framework->getAdapter(Input::class);
            $controllerAdapter = $this->framework->getAdapter(Controller::class);
            $systemAdapter = $this->framework->getAdapter(System::class);

            // Berechtigungen prüfen
            if (!$this->authorizationChecker->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'caeli_content_creator')) {
                $this->logger->warning('Zugriff verweigert für Content Creator', ['id' => $id]);
                throw new AccessDeniedException('Nicht genügend Berechtigungen für den Zugriff auf dieses Modul.');
            }

            // Modell laden
            $model = CaeliContentCreatorModel::findById($id);
            if (null === $model) {
                $this->logger->error('Content Creator Konfiguration nicht gefunden', ['id' => $id]);
                throw new \InvalidArgumentException('Content Creator-Konfiguration mit ID "'.$id.'" nicht gefunden.');
            }

            // Nur Veröffentlichung hier verarbeiten (Generierung läuft async)
            if ($request->isMethod('POST') && $request->request->has('publish')) {
                return $this->handlePublish($model);
            }

            // Template erstellen
            $template = new BackendTemplate('be_caeli_content_creator_preview');
            $template->headline = 'Inhaltsvorschau';
            $template->backUrl = $systemAdapter->getReferer();

            // Laden der NewsArchive-Daten
            $newsArchive = NewsArchiveModel::findById($model->newsArchive);
            if (null === $newsArchive) {
                $this->logger->error('Nachrichtenarchiv nicht gefunden', ['archive_id' => $model->newsArchive]);
                throw new \InvalidArgumentException('Nachrichtenarchiv nicht gefunden.');
            }

            // Template mit Daten füllen
            $template->newsArchive = $newsArchive->title;
            $template->contentElement = $model->contentElement;
            $template->topic = $model->topic;
            $template->modelId = $model->id;
            
            // Prüfe Generierungsstatus
            $generationStatus = $this->getGenerationStatus($model->id);
            $template->generationStatus = $generationStatus;
            
            // Nur wenn es eine fertige Vorschau gibt
            if ($model->previewTitle && $model->previewContent && $generationStatus === 'completed') {
                $template->previewExists = true;
                $template->previewTitle = $model->previewTitle;
                
                // Der Teaser soll nur angezeigt werden, wenn er sich vom Titel unterscheidet
                if ($model->previewTeaser && $model->previewTeaser !== $model->previewTitle) {
                    $template->previewTeaser = $model->previewTeaser;
                } else {
                    $template->previewTeaser = '';
                }
                
                $template->previewContent = $model->previewContent;
                $template->previewTags = $model->previewTags;
            } else {
                $template->previewExists = false;
            }

            return new Response($template->parse());
        } catch (\Exception $e) {
            $this->logger->error('Fehler in Preview', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Einfache Fehleranzeige zurückgeben
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '<h1>Fehler</h1><p>' . $e->getMessage() . '</p>';
            $template->backUrl = $this->framework->getAdapter(System::class)->getReferer();

            return new Response($template->parse());
        }
    }

    /**
     * AJAX-Endpunkt für asynchrone Content-Generierung
     */
    #[Route('/generate/{id}', name: 'caeli_content_creator_generate', methods: ['POST'])]
    public function generateContent(Request $request, int $id): JsonResponse
    {
        try {
            $this->framework->initialize();

            // Berechtigungen prüfen
            if (!$this->authorizationChecker->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'caeli_content_creator')) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Nicht genügend Berechtigungen'
                ], Response::HTTP_FORBIDDEN);
            }

            // Modell laden
            $model = CaeliContentCreatorModel::findById($id);
            if (null === $model) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Konfiguration nicht gefunden'
                ], Response::HTTP_NOT_FOUND);
            }

            // Status auf "generating" setzen
            $this->setGenerationStatus($id, 'generating');

            $this->logger->info('Starte async Content-Generierung', ['model_id' => $model->id]);

            try {
                // Content generieren
                $generatedContent = $this->performContentGeneration($model);
                
                // Im Modell speichern
                $model->previewTitle = $generatedContent['title'] ?? '';
                $model->previewTeaser = $generatedContent['teaser'] ?? '';
                $model->previewContent = $this->cleanHtmlContent($generatedContent['content'] ?? '');
                $model->previewTags = $generatedContent['tags'] ?? '';
                $model->save();

                // Status auf "completed" setzen
                $this->setGenerationStatus($id, 'completed');
                
                $this->logger->info('Async Content-Generierung erfolgreich', [
                    'model_id' => $model->id,
                    'title' => $model->previewTitle
                ]);

                return new JsonResponse([
                    'success' => true,
                    'message' => 'Inhalt erfolgreich generiert',
                    'data' => [
                        'title' => $model->previewTitle,
                        'teaser' => $model->previewTeaser,
                        'content' => $model->previewContent,
                        'tags' => $model->previewTags,
                        'attempt' => $generatedContent['attempt'] ?? 1
                    ]
                ]);

            } catch (\Exception $e) {
                // Status auf "error" setzen
                $this->setGenerationStatus($id, 'error', $e->getMessage());
                
                $this->logger->error('Fehler bei async Content-Generierung', [
                    'model_id' => $model->id,
                    'error' => $e->getMessage()
                ]);

                return new JsonResponse([
                    'success' => false,
                    'message' => 'Fehler bei der Generierung: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Exception $e) {
            $this->logger->error('Allgemeiner Fehler bei Content-Generierung', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'Allgemeiner Fehler: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * AJAX-Endpunkt für Status-Abfrage der Content-Generierung
     */
    #[Route('/status/{id}', name: 'caeli_content_creator_status', methods: ['GET'])]
    public function getStatus(Request $request, int $id): JsonResponse
    {
        try {
            $status = $this->getGenerationStatus($id);
            $progress = $this->getGenerationProgress($id);
            
            $response = [
                'success' => true,
                'status' => $status,
                'progress' => $progress
            ];

            // Bei completed Status auch die Daten mitliefern
            if ($status === 'completed') {
                $model = CaeliContentCreatorModel::findById($id);
                if ($model && $model->previewTitle) {
                    $response['data'] = [
                        'title' => $model->previewTitle,
                        'teaser' => $model->previewTeaser,
                        'content' => $model->previewContent,
                        'tags' => $model->previewTags
                    ];
                }
            }

            return new JsonResponse($response);
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generiert den Inhalt mit der KI-API (2-Step-Prozess)
     */
    private function performContentGeneration(CaeliContentCreatorModel $model): array
    {
        $this->logger->info('Starte 2-Step Content-Generierung', [
            'model_id' => $model->id,
            'topic' => $model->topic
        ]);
        
        try {
            // **SCHRITT 1: Rohtext generieren**
            $this->setGenerationProgress($model->id, 10);
            $this->logger->debug('Step 1: Generating raw text');
            
            $rawTextPrompt = $this->promptBuilder->buildPrompt($model, true);
            $rawTextResponse = $this->grokApiService->callApi(
                $model->apiKey,
                $model->apiEndpoint,
                $rawTextPrompt,
                (float)$model->temperature,
                (int)$model->maxTokens,
                (float)$model->topP
            );
            
            $this->setGenerationProgress($model->id, 50);
            $this->logger->debug('Step 1 completed', ['raw_text_length' => strlen($rawTextResponse)]);
            
            // **SCHRITT 2: HTML-Formatierung**
            $this->logger->debug('Step 2: Formatting to HTML');
            
            $formatPrompt = $this->promptBuilder->buildFormatPrompt($model, $rawTextResponse);
            $formattedResponse = $this->grokApiService->callApi(
                $model->apiKey,
                $model->apiEndpoint,
                $formatPrompt,
                (float)$model->temperature * 0.8, // Niedrigere Temperature für Formatierung
                (int)$model->maxTokens,
                (float)$model->topP
            );
            
            $this->setGenerationProgress($model->id, 80);
            
            // **SCHRITT 3: Content parsen**
            $this->logger->debug('Step 3: Parsing formatted content');
            $generatedContent = $this->promptBuilder->parseGeneratedContent($formattedResponse);
            
            // Content-Qualität prüfen
            if (!$this->validateContentQuality($generatedContent)) {
                throw new \RuntimeException('Generierter Content entspricht nicht den Qualitätsstandards.');
            }
            
            // **SCHRITT 4: Duplikat-Check**
            $this->setGenerationProgress($model->id, 90);
            $duplicates = $this->duplicateChecker->checkForDuplicates(
                $model->newsArchive,
                $generatedContent['title'],
                $generatedContent['teaser'] ?? ''
            );
            
            $seriousDuplicates = array_filter($duplicates, function($dup) {
                return (isset($dup['duplicateType']) && in_array($dup['duplicateType'], ['exact_match', 'partial_match'])) 
                    || (isset($dup['exactMatch']) && $dup['exactMatch'] === true);
            });
            
            $result = [
                'title' => $generatedContent['title'],
                'teaser' => $generatedContent['teaser'] ?? '',
                'content' => $this->cleanHtmlContent($generatedContent['content']),
                'tags' => $generatedContent['tags'] ?? '',
                'duplicates' => $duplicates,
                'serious_duplicates_count' => count($seriousDuplicates),
                'raw_text_length' => strlen($rawTextResponse),
                'final_content_length' => strlen($generatedContent['content'])
            ];
            
            $this->logger->info('2-Step Content-Generierung erfolgreich', [
                'model_id' => $model->id,
                'title' => $result['title'],
                'serious_duplicates' => count($seriousDuplicates),
                'raw_length' => $result['raw_text_length'],
                'final_length' => $result['final_content_length']
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler bei 2-Step Content-Generierung', [
                'model_id' => $model->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generiert eine URL zum Bearbeiten des News-Eintrags
     */
    private function generateNewsUrl(int $newsId): string
    {
        return '/contao?do=news&table=tl_content&id=' . $newsId;
    }

    /**
     * Bereinigt HTML-Inhalte und entfernt Escape-Sequenzen
     */
    private function cleanHtmlContent(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        // HTML-Entities dekodieren
        $content = html_entity_decode($content);
        
        // Backslashes vor Anführungszeichen entfernen
        $content = stripslashes($content);
        
        // Unicode-Escapesequenzen ersetzen
        $content = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
        }, $content);
        
        // Bootstrap-Container korrekt rendern
        $content = str_replace('\"', '"', $content);
        
        $this->logger->debug('HTML-Inhalt bereinigt', [
            'original_length' => strlen($content),
            'contains_headings' => (bool)preg_match('/<h[2-3][^>]*>/', $content),
            'contains_bootstrap' => (bool)preg_match('/class="(btn|card|alert|table)/', $content)
        ]);
        
        return $content;
    }

    /**
     * Verarbeitet die Veröffentlichung eines generierten Inhalts
     */
    private function handlePublish(CaeliContentCreatorModel $model): Response
    {
        $this->logger->info('Starte Veröffentlichung', ['model_id' => $model->id]);
        
        try {
            if (!$model->previewTitle || !$model->previewContent) {
                throw new \InvalidArgumentException('Kein Inhalt zum Veröffentlichen vorhanden. Bitte zuerst Inhalt generieren.');
            }

            // Nachrichtenbeitrag erstellen
            $newsId = $this->newsContentGenerator->createNewsArticle(
                $model->newsArchive,
                $model->previewTitle,
                $model->previewTeaser,
                $model->previewContent,
                $model->previewTags,
                $model->contentElement
            );

            $this->logger->info('Artikel erfolgreich veröffentlicht', [
                'model_id' => $model->id,
                'news_id' => $newsId
            ]);

            // Zurück zum DCA mit Erfolgsmeldung
            $controllerAdapter = $this->framework->getAdapter(Controller::class);
            return $controllerAdapter->redirect($controllerAdapter->addToUrl('do=caeli_content_creator&table=tl_caeli_content_creator&act=edit&id='.$model->id.'&confirmMsg=Inhalt wurde erfolgreich als Beitrag veröffentlicht.', true));
            
        } catch (\Exception $e) {
            $this->logger->error('Fehler beim Veröffentlichen', [
                'model_id' => $model->id,
                'error' => $e->getMessage()
            ]);
            
            // Fehlertemplate erstellen
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '<h1>Fehler beim Veröffentlichen</h1><p>' . $e->getMessage() . '</p>';
            $template->backUrl = $this->framework->getAdapter(System::class)->getReferer();
            
            return new Response($template->parse());
        }
    }

    /**
     * Setzt oder holt den Generierungsstatus
     */
    private function getGenerationStatus(int $modelId): string
    {
        $cacheKey = "content_generation_status_{$modelId}";
        
        // Hier würde normalerweise ein Cache-System verwendet
        // Für die einfache Implementierung verwenden wir Session
        $adapter = $this->framework->getAdapter(System::class);
        $session = $adapter->getContainer()->get('session');
        
        return $session->get($cacheKey, 'idle');
    }

    private function setGenerationStatus(int $modelId, string $status, string $message = ''): void
    {
        $cacheKey = "content_generation_status_{$modelId}";
        
        $adapter = $this->framework->getAdapter(System::class);
        $session = $adapter->getContainer()->get('session');
        
        $session->set($cacheKey, $status);
        
        if ($message) {
            $session->set($cacheKey . '_message', $message);
        }
        
        $this->logger->debug('Generation status updated', [
            'model_id' => $modelId,
            'status' => $status,
            'message' => $message
        ]);
    }

    /**
     * Setzt oder holt den Generierungsfortschritt
     */
    private function getGenerationProgress(int $modelId): int
    {
        $cacheKey = "content_generation_progress_{$modelId}";
        
        $adapter = $this->framework->getAdapter(System::class);
        $session = $adapter->getContainer()->get('session');
        
        return (int)$session->get($cacheKey, 0);
    }

    private function setGenerationProgress(int $modelId, int $progress): void
    {
        $cacheKey = "content_generation_progress_{$modelId}";
        
        $adapter = $this->framework->getAdapter(System::class);
        $session = $adapter->getContainer()->get('session');
        
        $session->set($cacheKey, $progress);
    }

    /**
     * Validiert die Qualität des generierten Contents
     */
    private function validateContentQuality(array $data): bool
    {
        $content = $data['content'] ?? '';
        $title = $data['title'] ?? '';

        // Grundlegende Checks
        if (empty($title) || empty($content)) {
            $this->logger->debug('Content-Validierung fehlgeschlagen: Leer', [
                'has_title' => !empty($title),
                'has_content' => !empty($content)
            ]);
            return false;
        }

        // Mindestlänge prüfen (ca. 500 Wörter = ~3000 Zeichen)
        $minContentLength = 2500;
        if (strlen($content) < $minContentLength) {
            $this->logger->debug('Content-Validierung fehlgeschlagen: Zu kurz', [
                'content_length' => strlen($content),
                'min_required' => $minContentLength
            ]);
            return false;
        }

        // Prüfen ob HTML-Struktur vorhanden (Überschriften)
        if (!preg_match('/<h[2-4][^>]*>/', $content)) {
            $this->logger->debug('Content-Validierung fehlgeschlagen: Keine Überschriften');
            return false;
        }

        // Prüfen auf Placeholder-Text
        $placeholderPatterns = [
            '/lorem ipsum/i',
            '/placeholder/i',
            '/\[.*?\]/i', // [Placeholder] Format
            '/TODO|FIXME/i'
        ];

        foreach ($placeholderPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $this->logger->debug('Content-Validierung fehlgeschlagen: Placeholder gefunden', [
                    'pattern' => $pattern
                ]);
                return false;
            }
        }

        // Prüfen auf sinnvolle Wortanzahl vs. HTML-Tags Verhältnis
        $textContent = strip_tags($content);
        $wordCount = str_word_count($textContent);
        $minWords = 400;

        if ($wordCount < $minWords) {
            $this->logger->debug('Content-Validierung fehlgeschlagen: Zu wenige Wörter', [
                'word_count' => $wordCount,
                'min_required' => $minWords
            ]);
            return false;
        }

        // Prüfen auf sinnvollen Titel (nicht nur Sonderzeichen)
        if (strlen(trim(preg_replace('/[^a-zA-ZäöüÄÖÜß\s]/', '', $title))) < 10) {
            $this->logger->debug('Content-Validierung fehlgeschlagen: Titel zu kurz oder nur Sonderzeichen');
            return false;
        }

        // Prüfen auf doppelte Absätze (Zeichen für Copy-Paste Fehler)
        $paragraphs = preg_split('/<\/p>\s*<p[^>]*>/', $content);
        $uniqueParagraphs = array_unique(array_map('trim', $paragraphs));
        
        if (count($paragraphs) > 3 && count($uniqueParagraphs) < count($paragraphs) * 0.8) {
            $this->logger->debug('Content-Validierung fehlgeschlagen: Zu viele doppelte Absätze');
            return false;
        }

        $this->logger->debug('Content-Validierung erfolgreich', [
            'content_length' => strlen($content),
            'word_count' => $wordCount,
            'title_length' => strlen($title),
            'paragraphs' => count($paragraphs)
        ]);

        return true;
    }
}
