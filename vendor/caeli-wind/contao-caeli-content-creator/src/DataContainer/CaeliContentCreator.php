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
use Contao\System;
use Doctrine\DBAL\Connection;
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
        private readonly PromptBuilder $promptBuilder
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
        
        // Prüfen, ob der "Inhalt generieren"-Button geklickt wurde
        if ($inputAdapter->post('generateContent') === '1') {
            // Modell laden
            $model = CaeliContentCreatorModel::findById($dc->id);
            if (null === $model) {
                Message::addError('Content Creator-Konfiguration nicht gefunden.');
                return;
            }
            
            try {
                // Inhalt generieren
                $generatedContent = $this->generateContent($model);
                
                // Vorschau im Modell speichern
                $model->previewTitle = $generatedContent['title'];
                $model->previewTeaser = $generatedContent['teaser'];
                $model->previewContent = $generatedContent['content'];
                $model->previewTags = $generatedContent['tags'];
                $model->save();
                
                Message::addConfirmation('Inhaltsvorschau wurde erstellt.');
            } catch (\Exception $e) {
                Message::addError('Fehler bei der Inhaltsgenerierung: ' . $e->getMessage());
            }
        }
        
        if ($inputAdapter->post('publishContent') === '1') {
            // Modell laden
            $model = CaeliContentCreatorModel::findById($dc->id);
            if (null === $model || !$model->previewTitle || !$model->previewContent) {
                Message::addError('Keine Vorschau zum Veröffentlichen vorhanden.');
                return;
            }
            
            try {
                // News-Artikel erstellen und veröffentlichen
                $newsId = $this->newsContentGenerator->createNewsArticle(
                    $model->newsArchive,
                    $model->previewTitle,
                    $model->previewTeaser,
                    $model->previewContent,
                    $model->previewTags,
                    $model->contentElement
                );
                
                Message::addConfirmation('Inhalt wurde erfolgreich als Beitrag veröffentlicht.');
            } catch (\Exception $e) {
                Message::addError('Fehler bei der Veröffentlichung: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Callback für das Generierungs-Button-Feld
     */
    public function generateButtonCallback(DataContainer $dc): string
    {
        $html = '<div class="widget">';
        $html .= '<h3><label>'.$GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][0].'</label></h3>';
        $html .= '<div class="tl_info">';
        $html .= '<p>'.$GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][1].'</p>';
        $html .= '</div>';
        
        // "Inhalt generieren"-Button
        $html .= '<div class="tl_submit_container" style="margin-top:10px;">';
        $html .= '<input type="hidden" name="generateContent" id="generateContent" value="0">';
        $html .= '<button type="button" class="tl_submit" style="background-color: #3498db;" 
                  onclick="document.getElementById(\'generateContent\').value=\'1\';document.getElementById(\'tl_caeli_content_creator\').submit();">' 
                  . ($GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][0] ?? 'Inhalt generieren') . '</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
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
     * Generiert den Inhalt für die Vorschau
     */
    private function generateContent(CaeliContentCreatorModel $model): array
    {
        // Zentralen PromptBuilder nutzen
        $prompt = $this->promptBuilder->buildPrompt($model);
        
        // API aufrufen und Antwort parsen
        $response = $this->grokApiService->callApi(
            $model->apiKey,
            $model->apiEndpoint,
            $prompt,
            (float)$model->temperature,
            (int)$model->maxTokens,
            (float)$model->topP
        );
        
        // JSON extrahieren und parsen
        if (preg_match('/\{[\s\S]*\}/m', $response, $matches)) {
            $json = $matches[0];
            
            // Debug-Log für die JSON-Antwort
            $logDir = $this->framework->getAdapter(System::class)->getContainer()->getParameter('kernel.logs_dir');
            file_put_contents($logDir . '/api-json-debug.log', "Erhaltenes JSON: " . $json . "\n", FILE_APPEND);
            
            // Versuchen, das JSON zu bereinigen und zu parsen
            try {
                // Versuche 1: Normales json_decode
                $data = json_decode($json, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Versuche 2: Bereinigen der Steuerzeichen und erneut versuchen
                    $cleanJson = preg_replace('/[\x00-\x1F\x7F]/', '', $json);
                    $data = json_decode($cleanJson, true);
                    
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Versuche 3: Alle Anführungszeichen innerhalb von Werten escapen
                        $cleanerJson = preg_replace('/"([^"]+)":/m', '"$1":', $cleanJson); // Schlüssel schützen
                        $cleanerJson = preg_replace('/:[ ]*"(.+)"([,}])/m', ': "'.addslashes('$1').'"$2', $cleanerJson);
                        $data = json_decode($cleanerJson, true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            // Letzte Chance: Manuelles Parsen durch Regex
                            preg_match('/"title"\s*:\s*"([^"]+)"/', $json, $titleMatch);
                            preg_match('/"teaser"\s*:\s*"([^"]*)"/', $json, $teaserMatch);
                            preg_match('/"content"\s*:\s*"([^"]*)"/', $json, $contentMatch);
                            preg_match('/"tags"\s*:\s*"([^"]*)"/', $json, $tagsMatch);
                            
                            $data = [
                                'title' => isset($titleMatch[1]) ? $titleMatch[1] : 'Kein Titel',
                                'teaser' => isset($teaserMatch[1]) ? $teaserMatch[1] : '',
                                'content' => isset($contentMatch[1]) ? $contentMatch[1] : 'Kein Inhalt',
                                'tags' => isset($tagsMatch[1]) ? $tagsMatch[1] : ''
                            ];
                            
                            // Log für manuelles Parsen
                            file_put_contents($logDir . '/api-json-debug.log', "Manuelles Parsen verwendet\n", FILE_APPEND);
                        }
                    }
                }
                
                // Bereinigung der Daten
                if (isset($data['content']) && is_string($data['content'])) {
                    // HTML-Tags für die Anzeige sichern
                    $data['content'] = str_replace('\\', '', $data['content']);
                    
                    // Entfernen aller zusätzlichen Escaping für HTML-Tags
                    $data['content'] = str_replace('&lt;', '<', $data['content']);
                    $data['content'] = str_replace('&gt;', '>', $data['content']);
                    $data['content'] = str_replace('&quot;', '"', $data['content']);
                    $data['content'] = str_replace('&#039;', "'", $data['content']);
                    
                    // Entfernen von doppelten Anführungszeichen-Escapes
                    $data['content'] = str_replace('\"', '"', $data['content']);
                    
                    // Entfernen von Unicode-Escapes
                    $data['content'] = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
                    }, $data['content']);
                    
                    // H1-Überschriften durch H2 ersetzen
                    $data['content'] = preg_replace('/<h1([^>]*)>(.*?)<\/h1>/i', '<h2$1>$2</h2>', $data['content']);
                    
                    // Entfernen unerwünschter Bootstrap-Abstandsklassen
                    $data['content'] = preg_replace('/(class="[^"]*)(m[tbxy]-\d+|p[tbxy]-\d+)([^"]*)/', '$1$3', $data['content']);
                    
                    // Entfernen von "d-grid" Klassen bei Buttons
                    $data['content'] = preg_replace('/<div class="[^"]*d-grid[^"]*">(\s*)<a/', '<a', $data['content']);
                    $data['content'] = preg_replace('/<\/a>(\s*)<\/div>/', '</a>', $data['content']);
                    
                    // Entfernen struktureller Bezeichnungen wie "Einleitung:" in Überschriften
                    $data['content'] = preg_replace('/<h[23][^>]*>(Einleitung|Fazit|Schluss|Zusammenfassung):\s*/', '<h$1>', $data['content']);
                    
                    // Prüfen auf falsche Quellenlinks (nur das Wort "Link" verlinkt)
                    if (preg_match('/<a[^>]*>Link<\/a>/', $data['content'])) {
                        file_put_contents($logDir . '/api-json-debug.log', "WARNUNG: Falsche Quellenlinks gefunden ('Link' statt vollständiger URL)\n", FILE_APPEND);
                    }
                    
                    // Prüfen auf Bootstrap-Elemente
                    $buttonMatches = [];
                    $bootstrapCheck = [
                        'buttons' => preg_match_all('/class="[^"]*btn[^"]*"/', $data['content'], $buttonMatches),
                        'cards' => preg_match('/class="[^"]*card[^"]*"/', $data['content']),
                        'alerts' => preg_match('/class="[^"]*alert[^"]*"/', $data['content']),
                        'tables' => preg_match('/class="[^"]*table[^"]*"/', $data['content'])
                    ];
                    
                    // Prüfen auf Button-Verteilung (Buttons sollten nicht alle am Ende stehen)
                    if ($bootstrapCheck['buttons'] >= 3) {
                        // Position des letzten Paragraphen
                        preg_match_all('/<p[^>]*>.*?<\/p>/s', $data['content'], $paragraphMatches, PREG_OFFSET_CAPTURE);
                        $lastParagraphPos = end($paragraphMatches[0])[1] ?? 0;
                        
                        // Position der Buttons
                        $buttonPositions = [];
                        preg_match_all('/<a[^>]*class="[^"]*btn[^"]*"[^>]*>.*?<\/a>/s', $data['content'], $buttonMatches, PREG_OFFSET_CAPTURE);
                        foreach ($buttonMatches[0] as $match) {
                            $buttonPositions[] = $match[1];
                        }
                        
                        // Prüfen, ob alle Buttons nach dem letzten Paragraphen stehen
                        $buttonsAtEnd = true;
                        foreach ($buttonPositions as $pos) {
                            if ($pos < $lastParagraphPos) {
                                $buttonsAtEnd = false;
                                break;
                            }
                        }
                        
                        if ($buttonsAtEnd) {
                            file_put_contents($logDir . '/api-json-debug.log', "WARNUNG: Alle Buttons stehen am Ende des Textes statt verteilt\n", FILE_APPEND);
                        }
                    }
                    
                    // Prüfen, ob der Teaser identisch mit dem Titel ist
                    if (isset($data['title']) && isset($data['teaser']) && trim($data['title']) === trim($data['teaser'])) {
                        // Teaser anpassen, damit er nicht identisch ist
                        $data['teaser'] = 'Erfahren Sie mehr über ' . $data['teaser'];
                    }
                    
                    // Debug-Log
                    file_put_contents($logDir . '/api-json-debug.log', "Bereinigter Content: " . substr($data['content'], 0, 500) . "...\n", FILE_APPEND);
                    file_put_contents($logDir . '/api-json-debug.log', "Bootstrap-Elemente gefunden: " . json_encode($bootstrapCheck) . "\n", FILE_APPEND);
                    if ($bootstrapCheck['buttons'] < 3) {
                        file_put_contents($logDir . '/api-json-debug.log', "WARNUNG: Weniger als 3 Buttons gefunden!\n", FILE_APPEND);
                    }
                    
                    // Wörter zählen
                    $wordCount = str_word_count(strip_tags($data['content']));
                    file_put_contents($logDir . '/api-json-debug.log', "Wortanzahl: " . $wordCount . "\n", FILE_APPEND);
                }
                
                return [
                    'title' => isset($data['title']) ? $data['title'] : 'Kein Titel',
                    'teaser' => isset($data['teaser']) ? $data['teaser'] : '',
                    'content' => isset($data['content']) ? $data['content'] : 'Kein Inhalt',
                    'tags' => isset($data['tags']) ? $data['tags'] : ''
                ];
            } catch (\Exception $e) {
                file_put_contents($logDir . '/api-json-debug.log', "JSON-Parsing-Fehler: " . $e->getMessage() . "\n", FILE_APPEND);
                throw new \RuntimeException('Fehler beim Parsen der API-Antwort: ' . $e->getMessage());
            }
        }
        
        throw new \RuntimeException('Konnte keine gültige JSON-Antwort von der API erhalten.');
    }

    /**
     * Callback für das Inhaltsgenerierungs-Button-Feld
     */
    public function generateContentButtonCallback(DataContainer $dc): string
    {
        $html = '<div class="widget">';
        $html .= '<h3><label>'.$GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][0].'</label></h3>';
        $html .= '<div class="tl_info">';
        $html .= '<p>'.$GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][1].'</p>';
        $html .= '</div>';
        
        // "Inhalt generieren"-Button
        $html .= '<div class="tl_submit_container" style="margin-top:10px;">';
        $html .= '<input type="hidden" name="generateContent" id="generateContent" value="0">';
        $html .= '<button type="button" class="tl_submit" style="background-color: #3498db;" 
                  onclick="document.getElementById(\'generateContent\').value=\'1\';document.getElementById(\'tl_caeli_content_creator\').submit();">' 
                  . ($GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'][0] ?? 'Inhalt generieren') . '</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}
