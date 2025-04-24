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

namespace CaeliWind\ContaoCaeliContentCreator\DataContainer;
 
use CaeliWind\ContaoCaeliContentCreator\Model\CaeliContentCreatorModel;
use CaeliWind\ContaoCaeliContentCreator\Service\GrokApiService;
use CaeliWind\ContaoCaeliContentCreator\Service\NewsContentGenerator;
use CaeliWind\ContaoCaeliContentCreator\Service\PromptBuilder;
use Contao\BackendUser;
use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\Model;
use Contao\System;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsCallback(table: 'tl_caeli_content_creator', target: 'edit.buttons', priority: 100)]
class CaeliContentCreator
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly GrokApiService $grokApiService,
        private readonly NewsContentGenerator $newsContentGenerator,
        private readonly PromptBuilder $promptBuilder,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(array $arrButtons, DataContainer $dc): array
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $systemAdapter = $this->framework->getAdapter(System::class);
        $controllerAdapter = $this->framework->getAdapter(Controller::class);

        $systemAdapter->loadLanguageFile('tl_caeli_content_creator');
        $controllerAdapter->loadLanguageFile('tl_content');

        // Wir brauchen keinen separaten Button mehr, da wir direkt in der DCA-Maske arbeiten
        return $arrButtons;
    }

    /**
     * Callback für onsubmit - prüft, ob der Generate-Button geklickt wurde und erzeugt dann Inhalte
     */
    public function onSubmitGenerateContent(DataContainer $dc): void
    {
        $inputAdapter = $this->framework->getAdapter(Input::class);
        $modelAdapter = $this->framework->getAdapter(CaeliContentCreatorModel::class);
        $messageAdapter = $this->framework->getAdapter(Message::class);

        // Prüfen, ob der "Inhalt generieren"-Button geklickt wurde
        if ($inputAdapter->post('generateContent') === '1') {
            $model = $modelAdapter->findById($dc->id);
            if (null === $model) {
                $messageAdapter->addError('Content Creator-Konfiguration nicht gefunden.');
                $this->logger->error('CaeliContentCreatorModel not found', ['id' => $dc->id]);
                return;
            }

            $this->logger->info('Starting two-step content generation', ['model_id' => $model->id]);

            try {
                // --- Schritt 1: Rohtext generieren ---
                $this->logger->debug('Step 1: Generating raw content prompt');
                $promptStep1 = $this->promptBuilder->buildPrompt($model, '@CaeliWindContaoCaeliContentCreator/prompt/step1_generate_raw.txt.twig');

                $this->logger->debug('Step 1: Calling Grok API for raw content');
                // maxTokens is now handled by service default based on config
                $responseStep1 = $this->grokApiService->callApi(
                    $model->apiKey,
                    $model->apiEndpoint,
                    $promptStep1,
                    (float) $model->temperature,
                    null,
                    (float) $model->topP
                );

                // **WORKAROUND: Treat responseStep1 directly as rawContent**
                $this->logger->debug('Step 1: Received raw text response from API', ['raw_snippet' => substr($responseStep1, 0, 200)]);

                // Clean potential markdown code fences (json, html, or none)
                $rawContent = preg_replace('/^```(?:json|html)?\s*/i', '', $responseStep1); // Remove opening ```, ```json, ```html
                $rawContent = preg_replace('/\s*```$/', '', $rawContent); // Remove closing ```
                $rawContent = trim($rawContent);

                // Basic check if the response is empty after cleaning
                if (empty($rawContent)) {
                    $this->logger->error('Step 1 response from API was empty or only contained markdown fences.', [
                        'original_response' => $responseStep1 // Log the original empty/fence response
                    ]);
                    throw new \RuntimeException('Die API lieferte leeren oder ungültigen Rohtext zurück. (Schritt 1)');
                }

                // Log die Wortanzahl des Rohtextes
                $wordCount = str_word_count($rawContent);
                $this->logger->info('Step 1: Raw content received and cleaned successfully', [
                    'length' => strlen($rawContent),
                    'word_count' => $wordCount
                ]);

                // --- Schritt 2: Text formatieren und anreichern ---
                $this->logger->debug('Step 2: Generating formatting prompt');
                // Pass rawContent in the context for the second prompt
                $promptStep2Template = '@CaeliWindContaoCaeliContentCreator/prompt/step2_format_enhance.txt.twig';
                $basePromptStep2 = $this->promptBuilder->buildPrompt($model, $promptStep2Template);
                // Replace the placeholder in the prompt with the actual raw content
                $promptStep2 = str_replace('{{ raw_content }}', $rawContent, $basePromptStep2);

                $this->logger->debug('Step 2: Calling Grok API for formatted content');
                // maxTokens is now handled by service default based on config
                $responseStep2 = $this->grokApiService->callApi(
                    $model->apiKey,
                    $model->apiEndpoint,
                    $promptStep2,
                    (float) $model->temperature,
                    null,
                    (float) $model->topP
                );

                $this->logger->debug('Step 2: Parsing final response using delimiters.');
                // Logge die komplette rohe Antwort von Schritt 2
                $this->logger->debug('Raw API response Step 2:', ['response' => $responseStep2]); 

                // Verwende Regex, um die Teile basierend auf den neuen Delimitern zu extrahieren
                $pattern = '/\[CONTENT_START\](.*)\[CONTENT_END\]\s*\[PAGETITLE_START\](.*)\[PAGETITLE_END\]\s*\[DESCRIPTION_START\](.*)\[DESCRIPTION_END\]\s*\[TAGS_START\](.*)\[TAGS_END\]/is';
                if (preg_match($pattern, $responseStep2, $matches)) {
                    $finalContent = trim($matches[1]);
                    $finalPageTitle = trim($matches[2]);
                    $finalDescription = trim($matches[3]);
                    $finalTags = trim($matches[4]);

                    // Logge die extrahierte Description
                    $this->logger->debug('Extracted description from regex:', ['description' => $finalDescription]);

                    // Bereinige den Content von Code Fences (falls doch noch vorhanden)
                    $finalContent = preg_replace('/^```(?:html)?\s*/i', '', $finalContent);
                    $finalContent = preg_replace('/\s*```$/', '', $finalContent);
                    $finalContent = trim($finalContent);

                    // Extrahiere den Teaser aus dem Content
                    $finalTeaser = '';
                    if (preg_match('/<div class=[\'\"]generated-teaser[\'\"][^>]*>(.*?)<\\/div>/is', $finalContent, $teaserMatches)) {
                        $finalTeaser = trim(strip_tags($teaserMatches[1]));
                        $this->logger->debug('Extracted teaser from content div.', ['teaser' => $finalTeaser]);
                        // Entferne das Teaser-Div aus dem Hauptinhalt
                        $finalContent = preg_replace('/<div class=[\'\"]generated-teaser[\'\"][^>]*>.*?<\\/div>/is', '', $finalContent, 1);
                        $finalContent = trim($finalContent);
                    } else {
                         $this->logger->warning('Could not extract teaser from <div class="generated-teaser"> within content block.');
                    }

                    $finalData = [
                        'title'     => $model->topic ?: 'Generierter Beitrag', // Nimm Topic als Basis, da Titel jetzt SEO-Titel ist
                        'pageTitle' => $finalPageTitle,
                        'teaser'    => $finalTeaser,
                        'content'   => $finalContent,
                        'description' => $finalDescription,
                        'tags'      => $finalTags
                    ];
                    $this->logger->info('Step 2: Final content parsed successfully using delimiters.', [
                        'title' => $finalData['title'],
                        'pageTitle' => $finalData['pageTitle']
                    ]);

                } else {
                    $this->logger->error('Failed to parse step 2 response using delimiters. Response did not match expected format.', [
                        'response_snippet' => substr($responseStep2, 0, 800) // Mehr Snippet loggen
                    ]);
                    // Logge, dass das Parsen fehlgeschlagen ist
                    $this->logger->error('Regex parsing failed for Step 2 response.');

                    // Fallback: Nimm die gesamte Antwort als Content, falls das Parsen fehlschlägt
                    $finalContent = preg_replace('/^```(?:html)?\s*/i', '', $responseStep2);
                    $finalContent = preg_replace('/\s*```$/', '', $finalContent);
                    $finalContent = trim($finalContent);
                    $finalData = [
                         'title'     => $model->topic ?: 'Generierter Beitrag',
                         'pageTitle' => '',
                         'teaser'    => '',
                         'content'   => $finalContent, 
                         'description' => '',
                         'tags'      => ''
                    ];
                     $this->logger->warning('Using fallback for Step 2 processing due to parsing error.');
                    // Optional: Hier keine Exception werfen, sondern mit Fallback weitermachen?
                    // throw new \RuntimeException('Die API lieferte keine korrekt formatierte Antwort zurück (Schritt 2, Delimiter Format).');
                }

                // Vorschau im Modell speichern (Annahme: Felder pageTitle und description existieren)
                $model->previewTitle = $finalData['title']; // Behalte Topic/Default als previewTitle
                $model->previewPageTitle = $finalData['pageTitle']; // Speichere generierten SEO-Titel
                $model->previewTeaser = $finalData['teaser'];
                $model->previewContent = $finalData['content'];
                $model->previewDescription = $finalData['description']; // Speichere generierte Beschreibung
                $model->previewTags = $finalData['tags'];
                $model->tstamp = time(); // Update timestamp
                $model->save();

                $messageAdapter->addConfirmation('Inhaltsvorschau wurde erfolgreich erstellt (2 Schritte).');

            } catch (\Exception $e) {
                $this->logger->error('Error during two-step content generation', ['exception' => $e]);
                $messageAdapter->addError('Fehler bei der Inhaltsgenerierung: ' . $e->getMessage());
            }
        }

        // Publish logic remains the same, using the preview fields
        if ($inputAdapter->post('publishContent') === '1') {
            $model = $modelAdapter->findById($dc->id);
            if (null === $model || empty($model->previewTitle) || empty($model->previewContent)) {
                $messageAdapter->addError('Keine Vorschau zum Veröffentlichen vorhanden.');
                return;
            }

            try {
                $newsId = $this->newsContentGenerator->createNewsArticle(
                    (int) $model->newsArchive,
                    (string) $model->previewTitle, // Der ursprüngliche Titel für die H1 der News
                    (string) $model->previewTeaser,
                    (string) $model->previewContent,
                    (string) $model->previewTags,
                    (string) $model->contentElement,
                    (string) $model->previewPageTitle, // Übergabe des SEO-Titels
                    (string) $model->previewDescription // Übergabe der SEO-Beschreibung
                );
                $this->logger->info('Content published successfully', ['model_id' => $model->id, 'news_id' => $newsId]);
                $messageAdapter->addConfirmation('Inhalt wurde erfolgreich als Beitrag (ID: ' . $newsId . ') veröffentlicht.');

                 // Clear preview fields after successful publishing?
                 // $model->previewTitle = '';
                 // $model->previewTeaser = '';
                 // $model->previewContent = '';
                 // $model->previewTags = '';
                 // $model->save();

            } catch (\Exception $e) {
                $this->logger->error('Error during publishing', ['model_id' => $model->id, 'exception' => $e]);
                $messageAdapter->addError('Fehler bei der Veröffentlichung: ' . $e->getMessage());
            }
        }
    }

    /**
     * Callback für das Vorschau-Anzeige-Feld
     */
    public function previewViewCallback(DataContainer $dc): string
    {
        // Modell laden
        $model = CaeliContentCreatorModel::findById($dc->id);
        if (null === $model || !$model->previewTitle || !$model->previewContent) {
            return '<div class="tl_info"><p>Es wurde noch kein Inhalt generiert. Klicken Sie auf "Inhalt generieren", um einen neuen Inhalt zu erstellen.</p></div>';
        }

        $html = '<div class="widget">';
        $html .= '<h3><label>'.$GLOBALS['TL_LANG']['tl_caeli_content_creator']['previewView'][0].'</label></h3>';

        // Vorschau-Anzeige
        $html .= '<div class="preview-container" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px;">';
        $html .= '<h2>' . $model->previewTitle . '</h2>';

        if ($model->previewTeaser) {
            $html .= '<div class="preview-teaser" style="font-style: italic; margin-bottom: 20px; padding: 10px; background: #f0f0f0;">';
            $html .= $model->previewTeaser;
            $html .= '</div>';
        }

        $html .= '<div class="preview-content">';
        $html .= $model->previewContent;
        $html .= '</div>';

        if ($model->previewTags) {
            $html .= '<div class="preview-tags" style="margin-top: 20px; color: #666;">';
            $html .= '<strong>Tags:</strong> ' . $model->previewTags;
            $html .= '</div>';
        }

        $html .= '</div>';

        // "Veröffentlichen"-Button
        $html .= '<div class="tl_submit_container">';
        $html .= '<input type="hidden" name="publishContent" id="publishContent" value="0">';
        $html .= '<button type="button" class="tl_submit" style="background-color: #27ae60;"
                  onclick="if(confirm(\'Möchten Sie diesen Inhalt wirklich veröffentlichen?\')) {document.getElementById(\'publishContent\').value=\'1\';document.getElementById(\'tl_caeli_content_creator\').submit();}">'
                  . ($GLOBALS['TL_LANG']['tl_caeli_content_creator']['publishContent'][0] ?? 'Inhalt veröffentlichen') . '</button>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Formatiert die Labels für die Listenansicht und stellt sicher, dass Datumsfelder korrekt behandelt werden
     */
    public function formatLabelCallback($row, $label, DataContainer $dc, $args)
    {
        // Einfache Implementierung - gib den args-Array unverändert zurück
        // Dies sollte funktionieren, solange keine Datumsformatierung benötigt wird
        return $args;
    }

    /**
     * Get all available content elements that can be used for blog content
     */
    public static function getContentElements(DataContainer $dc): array
    {
        $framework = System::getContainer()->get('contao.framework');
        $systemAdapter = $framework->getAdapter(System::class);
        $controllerAdapter = $framework->getAdapter(Controller::class);

        $controllerAdapter->loadLanguageFile('tl_content');
        $systemAdapter->loadLanguageFile('rocksolid_custom_elements');

        $contentElements = [];

        // Standard Contao-Elemente hinzufügen
        $contentElements['text'] = $GLOBALS['TL_LANG']['CTE']['text'][0] ?? 'Text';
        //$contentElements['headline'] = $GLOBALS['TL_LANG']['CTE']['headline'][0] ?? 'Überschrift';
        $contentElements['list'] = $GLOBALS['TL_LANG']['CTE']['list'][0] ?? 'Aufzählung';

        // RockSolid-Elemente über die Sprache ermitteln, sofern vorhanden
        if (isset($GLOBALS['TL_LANG']['rocksolid_custom_elements'])) {
            foreach ($GLOBALS['TL_LANG']['rocksolid_custom_elements'] as $key => $element) {
                if (is_array($element) && isset($element[0])) {
                    $contentElements['rsce_' . $key] = $element[0];
                }
            }
        }

        // Zusätzlich nach Dateien im templates-Verzeichnis suchen
        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $templateFiles = glob($projectDir . '/templates/rsce_*.html5');

        if ($templateFiles) {
            foreach ($templateFiles as $file) {
                $filename = basename($file, '.html5');
                // Nur hinzufügen, wenn es nicht bereits über die Sprache hinzugefügt wurde
                if (!isset($contentElements[$filename])) {
                    // Name aus dem Dateinamen extrahieren - rsce_name wird zu "Name"
                    $name = ucfirst(str_replace('rsce_', '', $filename));
                    $contentElements[$filename] = $name;
                }
            }
        }

        return $contentElements;
    }

    /**
     * Callback für das Inhaltsgenerierungs-Button-Feld
     */
    public function generateContentButtonCallback(DataContainer $dc): string
    {
        $generateButtonLabel = $GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][0] ?? 'Inhalt generieren';
        $generateButtonHelp = $GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][1] ?? '';
        $loadingText = $GLOBALS['TL_LANG']['tl_caeli_content_creator']['loadingText'][0] ?? 'Generiere Inhalt... Bitte warten.'; // Lade-Text holen

        $html = '<div class="widget">';
        $html .= '<h3><label>'.$generateButtonLabel.'</label></h3>';
        if ($generateButtonHelp) {
            $html .= '<div class="tl_info">';
            $html .= '<p>'.$generateButtonHelp.'</p>';
            $html .= '</div>';
        }

        // Ladeindikator (zunächst versteckt)
        $html .= '<div id="ccc-loading-indicator" style="display:none; margin-top:10px; padding:10px; background-color:#f0f0f0; border:1px solid #ccc;"><strong>'.$loadingText.'</strong></div>';

        // "Inhalt generieren"-Button ohne direktes onclick
        $html .= '<div class="tl_submit_container" style="margin-top:10px;">';
        $html .= '<input type="hidden" name="generateContent" id="generateContent" value="0">';
        $html .= '<button type="button" id="generate-content-btn" class="tl_submit" style="background-color: #3498db;">'
                  . $generateButtonLabel . '</button>';
        $html .= '</div>';

        // JavaScript zur Steuerung des Buttons und des Formular-Submits
        $html .= '<script>
        (function() {
            var btn = document.getElementById("generate-content-btn");
            var indicator = document.getElementById("ccc-loading-indicator");
            var hiddenInput = document.getElementById("generateContent");
            var form = btn ? btn.closest("form") : null; // Finde das umgebende Formular

            if (btn && indicator && hiddenInput && form) {
                btn.addEventListener("click", function(event) {
                    // Button deaktivieren und Ladeindikator anzeigen
                    this.disabled = true;
                    indicator.style.display = "block";

                    // Verstecktes Feld setzen
                    hiddenInput.value = "1";

                    // Formular abschicken
                    form.submit();
                });
            } else {
                if (!btn) console.error("Generate button not found.");
                if (!indicator) console.error("Loading indicator not found.");
                if (!hiddenInput) console.error("Hidden input field not found.");
                if (!form) console.error("Parent form not found.");
            }
        })();
        </script>';


        $html .= '</div>'; // widget schließen

        return $html;
    }
}
