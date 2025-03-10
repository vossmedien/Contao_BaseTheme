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

use CaeliWind\ContaoCaeliContentCreator\Model\ContentCreatorModel;
use CaeliWind\ContaoCaeliContentCreator\Service\GrokApiService;
use CaeliWind\ContaoCaeliContentCreator\Service\NewsContentGenerator;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/contao/caeli_content_creator', defaults: ['_scope' => 'backend'])]
class ContentCreatorController extends AbstractBackendController
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly GrokApiService $grokApiService,
        private readonly NewsContentGenerator $newsContentGenerator,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
        // Kein parent::__construct() mehr in Contao 5.5 / Symfony 6
    }

    /**
     * Zeigt die Vorschau für den generierten Inhalt an
     */
    #[Route('/preview/{id}', name: 'caeli_content_creator_preview')]
    public function preview(Request $request, int $id): Response
    {
        try {
            $this->framework->initialize();

            // Debug-Ausgabe
            $systemAdapter = $this->framework->getAdapter(System::class);
            $logFile = $systemAdapter->getContainer()->getParameter('kernel.logs_dir') . '/preview-debug.log';
            file_put_contents($logFile, "Starte Preview mit ID: $id\n", FILE_APPEND);

            $inputAdapter = $this->framework->getAdapter(Input::class);
            $controllerAdapter = $this->framework->getAdapter(Controller::class);

            // Berechtigungen prüfen
            if (!$this->authorizationChecker->isGranted(ContaoCorePermissions::USER_CAN_ACCESS_MODULE, 'caeli_content_creator')) {
                file_put_contents($logFile, "Zugriff verweigert\n", FILE_APPEND);
                throw new AccessDeniedException('Nicht genügend Berechtigungen für den Zugriff auf dieses Modul.');
            }

            // Modell laden
            $model = ContentCreatorModel::findById($id);
            if (null === $model) {
                file_put_contents($logFile, "Modell nicht gefunden für ID: $id\n", FILE_APPEND);
                throw new \InvalidArgumentException('Content Creator-Konfiguration mit ID "'.$id.'" nicht gefunden.');
            }

            // Prüfen, ob Inhalt generiert werden soll
            $generate = $request->isMethod('POST') && $request->request->has('generate');
            $publish = $request->isMethod('POST') && $request->request->has('publish');
            $generatedContent = null;
            
            file_put_contents($logFile, "Generate: " . ($generate ? "ja" : "nein") . ", Publish: " . ($publish ? "ja" : "nein") . "\n", FILE_APPEND);
            
            if ($generate) {
                try {
                    file_put_contents($logFile, "Starte Generierung für Modell mit ID: " . $model->id . "\n", FILE_APPEND);
                    
                    // Inhalt generieren
                    $generatedContent = $this->generateContent($model);
                    
                    // Im Modell speichern
                    $model->previewTitle = $generatedContent['title'] ?? '';
                    $model->previewTeaser = $generatedContent['teaser'] ?? '';
                    $model->previewContent = $generatedContent['content'] ?? '';
                    $model->previewTags = $generatedContent['tags'] ?? '';
                    
                    // Sonderzeichen in HTML-Inhalten bereinigen und sicherstellen, dass HTML nicht escaped wird
                    if (!empty($model->previewContent)) {
                        // HTML-Entities dekodieren, falls sie in der Antwort enthalten sind
                        $model->previewContent = html_entity_decode($model->previewContent);
                        
                        // Sicherstellen, dass keine Backslashes vor Anführungszeichen in HTML-Attributen stehen
                        $model->previewContent = stripslashes($model->previewContent);
                        
                        // Ersetzen von Unicode-Escapesequenzen
                        $model->previewContent = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                        }, $model->previewContent);
                        
                        // Stellen Sie sicher, dass Bootstrap-Container-Elemente korrekt gerendert werden
                        $model->previewContent = str_replace('\"', '"', $model->previewContent);
                        
                        // Debug-Ausgabe des Inhalts für die Fehlersuche
                        file_put_contents($logFile, "HTML-Inhalt final (ersten 1000 Zeichen): " . substr($model->previewContent, 0, 1000) . "...\n", FILE_APPEND);
                        
                        // Prüfen, ob wichtige HTML-Elemente vorhanden sind
                        $containsHeadings = preg_match('/<h[2-3][^>]*>/', $model->previewContent);
                        $containsBootstrap = preg_match('/class="(btn|card|alert|table)/', $model->previewContent);
                        
                        file_put_contents($logFile, "HTML-Check: Überschriften vorhanden: " . ($containsHeadings ? 'Ja' : 'Nein') . 
                                           ", Bootstrap-Elemente vorhanden: " . ($containsBootstrap ? 'Ja' : 'Nein') . "\n", FILE_APPEND);
                    }
                    
                    $model->save();
                    
                    file_put_contents($logFile, "Generierung abgeschlossen, Titel: " . $model->previewTitle . "\n", FILE_APPEND);
                    
                    // Erfolgsmeldung
                    $template->message = 'Inhaltsvorschau wurde erfolgreich erstellt.';
                } catch (\Exception $e) {
                    file_put_contents($logFile, "Fehler bei der Generierung: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);
                    throw $e;
                }
            }
            
            // Veröffentlichen des Inhalts
            if ($publish && $model->previewTitle && $model->previewContent) {
                file_put_contents($logFile, "Veröffentliche Inhalt\n", FILE_APPEND);
                try {
                    // Nachrichtenbeitrag erstellen
                    $newsId = $this->newsContentGenerator->createNewsArticle(
                        $model->newsArchive,
                        $model->previewTitle,
                        $model->previewTeaser,
                        $model->previewContent,
                        $model->previewTags,
                        $model->contentElement
                    );

                    file_put_contents($logFile, "Artikel erfolgreich erstellt mit ID: $newsId\n", FILE_APPEND);

                    // Zurück zum DCA mit Erfolgsmeldung
                    $controllerAdapter->redirect($controllerAdapter->addToUrl('do=caeli_content_creator&table=tl_caeli_content_creator&act=edit&id='.$id.'&confirmMsg=Inhalt wurde erfolgreich als Beitrag veröffentlicht.', true));
                } catch (\Exception $e) {
                    file_put_contents($logFile, "FEHLER: " . $e->getMessage() . "\n", FILE_APPEND);
                    $template->message = 'Fehler beim Veröffentlichen: ' . $e->getMessage();
                }
            }

            // Template erstellen
            $template = new BackendTemplate('be_caeli_content_creator_preview');
            $template->headline = 'Inhaltsvorschau';
            $template->backUrl = $systemAdapter->getReferer();

            // Laden der NewsArchive-Daten
            $newsArchive = NewsArchiveModel::findById($model->newsArchive);
            if (null === $newsArchive) {
                file_put_contents($logFile, "Nachrichtenarchiv nicht gefunden\n", FILE_APPEND);
                throw new \InvalidArgumentException('Nachrichtenarchiv nicht gefunden.');
            }

            // Template mit Daten füllen
            $template->newsArchive = $newsArchive->title;
            $template->contentElement = $model->contentElement;
            $template->topic = $model->topic;
            
            // Nur wenn es eine Vorschau gibt
            if ($model->previewTitle && $model->previewContent) {
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

            // Debug-Informationen aus Logs sammeln
            $debugInfo = '';
            if (file_exists($logDir . '/api-debug.log')) {
                $debugInfo .= "API-Debug-Log:\n" . $this->getLastLogEntries($logDir . '/api-debug.log') . "\n\n";
            }
            if (file_exists($logDir . '/newsgen-debug.log')) {
                $debugInfo .= "News-Generator-Log:\n" . $this->getLastLogEntries($logDir . '/newsgen-debug.log') . "\n\n";
            }
            $template->debug_info = $debugInfo;

            // Template parsen
            $content = $template->parse();
            file_put_contents($logFile, "Template wurde geparsed\n", FILE_APPEND);

            return new Response($content);
        } catch (\Exception $e) {
            // Debug-Logfile für Fehler
            $logFile = $this->framework->getAdapter(System::class)->getContainer()->getParameter('kernel.logs_dir') . '/preview-error.log';
            file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " Fehler in Preview: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n",
                FILE_APPEND
            );

            // Einfache Fehleranzeige zurückgeben
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '<h1>Fehler</h1><p>' . $e->getMessage() . '</p>';
            $template->backUrl = $this->framework->getAdapter(System::class)->getReferer();

            return new Response($template->parse());
        }
    }

    /**
     * AJAX-Endpunkt zum Generieren und Veröffentlichen von Inhalt
     */
    #[Route('/publish', name: 'caeli_content_creator_publish', methods: ['POST'])]
    public function publishContent(Request $request): Response
    {
        $id = $request->request->get('id');

        // Modell laden
        $model = ContentCreatorModel::findById($id);
        if (null === $model) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Datensatz nicht gefunden'
            ], Response::HTTP_NOT_FOUND);
        }

        // Generierte Inhalte aus der Session laden
        $session = $request->getSession();
        $sessionKey = 'caeli_content_creator_' . $id;
        $generatedContent = $session->get($sessionKey);

        if (!$generatedContent) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Kein generierter Inhalt gefunden. Bitte zuerst Inhalt generieren.'
            ]);
        }

        try {
            // News-Artikel erstellen mit Bildunterstützung
            $newsId = $this->newsContentGenerator->createNewsArticle(
                (int) $model->newsArchive,
                $generatedContent['title'] ?? 'Generierter Artikel',
                $generatedContent['teaser'] ?? '',
                $generatedContent['content'] ?? '',
                $generatedContent['tags'] ?? '',
                $model->contentElement
            );

            // Session bereinigen
            $session->remove($sessionKey);

            // Erfolgsmeldung zurückgeben
            return new JsonResponse([
                'success' => true,
                'message' => 'Artikel erfolgreich erstellt',
                'newsId' => $newsId,
                'newsUrl' => $this->generateNewsUrl($newsId)
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Fehler beim Erstellen des Artikels: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Generiert den Inhalt mit der Grok-API
     */
    private function generateContent(ContentCreatorModel $model): array
    {
        // Debug-Ausgabe
        $logFile = $this->framework->getAdapter(System::class)->getContainer()->getParameter('kernel.logs_dir') . '/api-debug.log';
        file_put_contents($logFile, "Starte Inhaltsgenerierung für Thema: {$model->topic}\n", FILE_APPEND);

        // Prompt vorbereiten
        $prompt = "Erstelle einen Blog-Artikel zum Thema: " . $model->topic . ".\n\n";

        if (!empty($model->targetAudience)) {
            $prompt .= "Zielgruppe: " . $model->targetAudience . "\n";
        }

        if (!empty($model->emphasis)) {
            $prompt .= "Besondere Betonung auf: " . $model->emphasis . "\n";
        }

        // Minimale Wortzahl hinzufügen, falls angegeben
        if (!empty($model->min_words)) {
            $prompt .= "Der Artikel sollte mindestens {$model->min_words} Wörter umfassen.\n";
        }

        // Quellenangaben anfordern, falls gewünscht
        if (!empty($model->include_sources)) {
            $prompt .= "Bitte füge am Ende des Artikels einige zitierbare Quellen ein.\n";
        }

        // Links in neuem Tab öffnen, falls gewünscht
        if (!empty($model->add_target_blank)) {
            $prompt .= "Alle Links im Artikel sollten in einem neuen Tab geöffnet werden (target=\"_blank\" Attribut).\n";
        }

        if (!empty($model->additionalInstructions)) {
            $prompt .= "Weitere Anweisungen: " . $model->additionalInstructions . "\n";
        }

        $prompt .= "\nBitte generiere im folgenden JSON-Format:
        {
            \"title\": \"Titel des Artikels\",
            \"teaser\": \"Kurze Zusammenfassung des Inhalts (2-3 Sätze)\",
            \"content\": \"Der vollständige Artikel mit HTML-Formatierung\",
            \"tags\": \"Kommagetrennte Liste von Tags für den Artikel\"
        }";

        file_put_contents($logFile, "Prompt erstellt, rufe API auf\n", FILE_APPEND);

        try {
            // API aufrufen und Antwort parsen
            $response = $this->grokApiService->callApi(
                $model->grokApiKey,
                $model->grokApiEndpoint,
                $prompt
            );

            file_put_contents($logFile, "API-Antwort erhalten, extrahiere JSON\n", FILE_APPEND);

            // JSON extrahieren und parsen
            if (preg_match('/\{[\s\S]*\}/m', $response, $matches)) {
                $json = $matches[0];
                $data = json_decode($json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    file_put_contents($logFile, "JSON-Parsing-Fehler: " . json_last_error_msg() . "\n", FILE_APPEND);
                    throw new \RuntimeException('Fehler beim Parsen der API-Antwort: ' . json_last_error_msg());
                }

                file_put_contents($logFile, "Inhalt erfolgreich generiert\n", FILE_APPEND);

                return [
                    'title' => $data['title'] ?? 'Kein Titel',
                    'teaser' => $data['teaser'] ?? '',
                    'content' => $data['content'] ?? 'Kein Inhalt',
                    'tags' => $data['tags'] ?? ''
                ];
            }

            file_put_contents($logFile, "Keine gültige JSON-Antwort gefunden\n", FILE_APPEND);
            throw new \RuntimeException('Konnte keine gültige JSON-Antwort von der API erhalten.');
        } catch (\Exception $e) {
            file_put_contents($logFile, "Fehler bei API-Aufruf: " . $e->getMessage() . "\n", FILE_APPEND);
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
}
