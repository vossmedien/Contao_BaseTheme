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
        private readonly NewsContentGenerator $newsContentGenerator
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
                // KI-Inhalt generieren
                $generatedContent = $this->generateContent($model);
                
                // Vorschaudaten im Modell speichern
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
        
        // Prüfen, ob der "Veröffentlichen"-Button geklickt wurde
        if ($inputAdapter->post('publishContent') === '1') {
            // Modell laden
            $model = CaeliContentCreatorModel::findById($dc->id);
            if (null === $model || !$model->previewTitle || !$model->previewContent) {
                Message::addError('Keine Vorschau zum Veröffentlichen vorhanden.');
                return;
            }
            
            try {
                // Nachrichtenbeitrag und Inhaltselement erstellen
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
        $contentElements['headline'] = $GLOBALS['TL_LANG']['CTE']['headline'][0] ?? 'Überschrift';
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
     * Generiert den Inhalt mit der Grok-API
     */
    private function generateContent(CaeliContentCreatorModel $model): array
    {
        // Prompt vorbereiten
        $prompt = "Erstelle einen Blog-Artikel zum Thema: " . $model->topic . ".\n\n";
        
        if (!empty($model->targetAudience)) {
            $prompt .= "Zielgruppe: " . $model->targetAudience . "\n";
        }
        
        if (!empty($model->emphasis)) {
            $prompt .= "Besondere Betonung auf: " . $model->emphasis . "\n";
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
        
        // API aufrufen und Antwort parsen
        $response = $this->grokApiService->callApi(
            $model->grokApiKey,
            $model->grokApiEndpoint,
            $prompt
        );
        
        // JSON extrahieren und parsen
        if (preg_match('/\{[\s\S]*\}/m', $response, $matches)) {
            $json = $matches[0];
            $data = json_decode($json, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Fehler beim Parsen der API-Antwort: ' . json_last_error_msg());
            }
            
            return [
                'title' => $data['title'] ?? 'Kein Titel',
                'teaser' => $data['teaser'] ?? '',
                'content' => $data['content'] ?? 'Kein Inhalt',
                'tags' => $data['tags'] ?? ''
            ];
        }
        
        throw new \RuntimeException('Konnte keine gültige JSON-Antwort von der API erhalten.');
    }
}
