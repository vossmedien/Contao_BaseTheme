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

namespace CaeliWind\ContaoCaeliContentCreator\Service;

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FilesModel;
use Contao\NewsModel;
use Contao\StringUtil;
use Contao\System;
use Doctrine\DBAL\Connection;

class NewsContentGenerator
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
    ) {
    }

    /**
     * Erstellt einen neuen Nachrichtenbeitrag mit dem generierten Inhalt.
     */
    public function createNewsArticle(
        int $archiveId,
        string $title,
        string $teaser,
        string $content,
        string $tags,
        string $elementType
    ): int {
        $this->framework->initialize();

        // Adapter für die verschiedenen Contao-Klassen
        $controllerAdapter = $this->framework->getAdapter(Controller::class);
        $newsAdapter = $this->framework->getAdapter(NewsModel::class);
        $contentModelAdapter = $this->framework->getAdapter(ContentModel::class);
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        
        // 1. Erstelle den Nachrichten-Eintrag
        $news = new NewsModel();
        $news->pid = $archiveId;
        $news->tstamp = time();
        $news->date = time();
        $news->time = time();
        $news->headline = $title;
        $news->alias = $this->generateAlias($title);
        $news->teaser = $teaser;
        $news->published = 1;
        
        // Tags hinzufügen, falls vorhanden
        if (!empty($tags)) {
            $news->tags = $tags;
        }
        
        $news->save();
        $newsId = $news->id;

        // 3. Inhaltselement basierend auf dem ausgewählten Typ erstellen
        $contentElement = new ContentModel();
        $contentElement->pid = $newsId;
        $contentElement->ptable = 'tl_news';
        $contentElement->tstamp = time();
        $contentElement->type = $elementType;
        
        // Wenn es sich um einen Text/HTML-Inhalt handelt
        if ($elementType === 'text' || $elementType === 'html') {
            $contentElement->headline = ['value' => $title, 'unit' => 'h1'];
            $contentElement->text = $content;
            $contentElement->save();
        } 
        // Wenn es sich um ein RockSolid Custom Element handelt
        elseif (strpos($elementType, 'rsce_') === 0) {
            $this->fillRockSolidElement($contentElement, $elementType, $title, $content);
            $contentElement->save();
        }

        return $newsId;
    }

    /**
     * Generiert einen eindeutigen Alias für den Nachrichten-Eintrag
     */
    private function generateAlias(string $title): string
    {
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $alias = $stringUtilAdapter->standardize($title);

        // Eindeutigkeit prüfen und sicherstellen
        $newsAdapter = $this->framework->getAdapter(NewsModel::class);
        $existingNews = $newsAdapter->findByAlias($alias);

        if (null !== $existingNews) {
            $alias .= '-' . time();
        }

        return $alias;
    }

    /**
     * Konvertiert HTML-Inhalt in ein Array von Listenpunkten
     */
    private function convertContentToListItems(string $htmlContent): array
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<div>' . $htmlContent . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $items = [];

        // Versuche, natürliche Listen zu finden
        $listElements = $dom->getElementsByTagName('li');
        if ($listElements->length > 0) {
            foreach ($listElements as $listItem) {
                $items[] = $listItem->nodeValue;
            }
        } else {
            // Keine Liste gefunden, verwende Absätze
            $paragraphs = $dom->getElementsByTagName('p');
            foreach ($paragraphs as $paragraph) {
                $items[] = $paragraph->nodeValue;
            }
        }

        // Wenn keine Absätze gefunden wurden, teile den Text nach Punkten auf
        if (empty($items)) {
            $items = array_filter(array_map('trim', explode('.', strip_tags($htmlContent))));
        }

        // Serialize für Contao
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        return $stringUtilAdapter->deserialize(serialize($items), true);
    }

    /**
     * Befüllt ein RockSolid Custom Element dynamisch basierend auf seiner Konfiguration
     */
    private function fillRockSolidElement(ContentModel $element, string $type, string $title, string $content): void
    {
        // Extrahiere den tatsächlichen Element-Namen ohne Präfix
        $elementName = substr($type, 5);

        // Debug-Info
        $logDir = System::getContainer()->getParameter('kernel.logs_dir');
        file_put_contents($logDir . '/rocksolid-debug.log', "Befülle RockSolid Element: $elementName\n", FILE_APPEND);

        // Verschiedene mögliche Konfigurationspfade prüfen
        $configPaths = [
            // Standard-Pfad in /templates/rsce/
            System::getContainer()->getParameter('kernel.project_dir') . '/templates/rsce/' . $elementName . '/config.php',
            // Alternatives Format in /templates/
            System::getContainer()->getParameter('kernel.project_dir') . '/templates/rsce_' . $elementName . '_config.php',
            // Direkter Pfad (falls der Elementname bereits mit "rsce_" beginnt)
            System::getContainer()->getParameter('kernel.project_dir') . '/templates/' . $elementName . '_config.php',
        ];

        $config = null;
        $configPath = null;

        // Prüfe alle möglichen Pfade
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                file_put_contents($logDir . '/rocksolid-debug.log', "Config gefunden unter: $path\n", FILE_APPEND);
                $configPath = $path;
                try {
                    $config = include $path;
                    break;
                } catch (\Exception $e) {
                    file_put_contents($logDir . '/rocksolid-debug.log', "Fehler beim Laden der Config: " . $e->getMessage() . "\n", FILE_APPEND);
                }
            }
        }

        // Wenn keine Konfiguration gefunden wurde, Fallback verwenden
        if (!$config) {
            file_put_contents($logDir . '/rocksolid-debug.log', "Keine Konfiguration gefunden, verwende Fallback\n", FILE_APPEND);
            $element->text = $content;
            return;
        }

        // Extrahiere und analysiere den Inhalt für besser strukturierte Elemente
        $dom = new \DOMDocument();
        @$dom->loadHTML('<div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);

        // Felder des Elements extrahieren
        $fields = $config['fields'] ?? [];
        file_put_contents($logDir . '/rocksolid-debug.log', "Verfügbare Felder: " . implode(", ", array_keys($fields)) . "\n", FILE_APPEND);

        // Standard-Felder befüllen wenn in standardFields definiert oder direkt als Feld vorhanden
        $standardFields = $config['standardFields'] ?? [];
        if (in_array('headline', $standardFields) || isset($fields['headline'])) {
            file_put_contents($logDir . '/rocksolid-debug.log', "Setze headline: $title\n", FILE_APPEND);
            $element->headline = $title;
        }

        // Headline-Typ automatisch setzen (h2 als Standard)
        if (isset($fields['headline_type'])) {
            file_put_contents($logDir . '/rocksolid-debug.log', "Setze headline_type: h2\n", FILE_APPEND);
            $element->headline_type = 'h2';  // Standardmäßig H2 verwenden
        }

        // Topline befüllen
        if (isset($fields['topline'])) {
            // Versuche aus dem DOM zu extrahieren oder generiere
            $toplineNodes = $xpath->query('//h1/preceding-sibling::*[1] | //h2/preceding-sibling::*[1]');
            if ($toplineNodes && $toplineNodes->length > 0) {
                $element->topline = $toplineNodes->item(0)->textContent;
            } else {
                // Generiere aus dem Titel eine passende Topline
                $element->topline = 'Thema: ' . ucfirst($elementName);
            }
            file_put_contents($logDir . '/rocksolid-debug.log', "Setze topline: {$element->topline}\n", FILE_APPEND);
        }

        // Subline befüllen
        if (isset($fields['subline'])) {
            // Versuche aus dem DOM zu extrahieren oder generiere
            $sublineNodes = $xpath->query('//h1/following-sibling::*[1] | //h2/following-sibling::*[1]');
            if ($sublineNodes && $sublineNodes->length > 0) {
                $element->subline = $sublineNodes->item(0)->textContent;
            } else {
                // Generiere eine Zusammenfassung
                $paragraphs = $xpath->query('//p');
                if ($paragraphs && $paragraphs->length > 0) {
                    $element->subline = substr($paragraphs->item(0)->textContent, 0, 150) . '...';
                }
            }
            file_put_contents($logDir . '/rocksolid-debug.log', "Setze subline: {$element->subline}\n", FILE_APPEND);
        }

        // Haupttext-Feld befüllen (desc, text, description, content, ...)
        $mainContentFields = ['desc', 'text', 'description', 'content', 'richtext', 'textarea'];
        $mainContentSet = false;

        foreach ($mainContentFields as $field) {
            if (isset($fields[$field])) {
                $element->$field = $content;
                file_put_contents($logDir . '/rocksolid-debug.log', "Setze $field mit Hauptinhalt\n", FILE_APPEND);
                $mainContentSet = true;
                break;
            }
        }

        // Wenn kein Haupttext-Feld gefunden wurde, in fallback_content speichern
        if (!$mainContentSet) {
            $element->fallback_content = $content;
            file_put_contents($logDir . '/rocksolid-debug.log', "Setze fallback_content\n", FILE_APPEND);
        }

        // Listen und verschachtelte Elemente verarbeiten
        foreach ($fields as $fieldName => $fieldConfig) {
            // Listen-Elemente identifizieren
            if (isset($fieldConfig['inputType']) && $fieldConfig['inputType'] === 'list' && isset($fieldConfig['fields'])) {
                file_put_contents($logDir . '/rocksolid-debug.log', "Befülle Listen-Element: $fieldName\n", FILE_APPEND);
                $this->fillListElement($element, $fieldName, $fieldConfig, $content, $xpath);
            }
        }

        // Debug: Alle gesetzten Werte loggen
        $elementVars = get_object_vars($element);
        file_put_contents($logDir . '/rocksolid-debug.log', "Alle gesetzten Werte:\n", FILE_APPEND);
        foreach ($elementVars as $key => $value) {
            if (in_array($key, ['id', 'pid', 'ptable', 'sorting', 'tstamp', 'type'])) {
                continue;
            }
            file_put_contents($logDir . '/rocksolid-debug.log', "- $key: " . (is_string($value) ? substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') : gettype($value)) . "\n", FILE_APPEND);
        }
    }

    /**
     * Befüllt ein Listen-Element in einem RockSolid-Element
     */
    private function fillListElement(ContentModel $element, string $fieldName, array $fieldConfig, string $content, \DOMXPath $xpath): void
    {
        $listData = [];
        $maxItems = $fieldConfig['maxItems'] ?? 3;
        $minItems = $fieldConfig['minItems'] ?? 1;

        // Identifiziere mögliche Listenelemente aus dem Inhalt
        $listItems = [];

        // Versuch 1: Suche nach Listen-Tags
        $ulElements = $xpath->query('//ul/li');
        if ($ulElements && $ulElements->length > 0) {
            foreach ($ulElements as $li) {
                $listItems[] = [
                    'text' => $li->textContent,
                    'html' => $li->ownerDocument->saveHTML($li)
                ];
            }
        }

        // Versuch 2: Suche nach Überschriften als Listenitems
        if (count($listItems) < $minItems) {
            $headings = $xpath->query('//h2 | //h3');
            if ($headings && $headings->length > 0) {
                $listItems = [];
                foreach ($headings as $heading) {
                    // Suche nach Inhalt zwischen dieser und der nächsten Überschrift
                    $currentNode = $heading->nextSibling;
                    $description = '';

                    while ($currentNode && !in_array(strtolower($currentNode->nodeName), ['h2', 'h3'])) {
                        if ($currentNode->nodeType === XML_ELEMENT_NODE) {
                            $description .= $currentNode->ownerDocument->saveHTML($currentNode);
                        }
                        $currentNode = $currentNode->nextSibling;
                    }

                    $listItems[] = [
                        'title' => $heading->textContent,
                        'description' => $description
                    ];
                }
            }
        }

        // Versuch 3: Teile den Inhalt in logische Abschnitte
        if (count($listItems) < $minItems) {
            $paragraphs = $xpath->query('//p');
            if ($paragraphs && $paragraphs->length > 0) {
                $listItems = [];
                $itemCount = min($maxItems, max($minItems, intval($paragraphs->length / 2)));

                // Teile die Paragraphen in gleichmäßige Gruppen
                $paragraphsPerItem = intval($paragraphs->length / $itemCount);
                for ($i = 0; $i < $itemCount; $i++) {
                    $title = "Punkt " . ($i + 1);
                    $description = '';

                    for ($j = 0; $j < $paragraphsPerItem && ($i * $paragraphsPerItem + $j) < $paragraphs->length; $j++) {
                        $paragraph = $paragraphs->item($i * $paragraphsPerItem + $j);
                        if ($j === 0) {
                            // Ersten Absatz als Titel verwenden
                            $title = $paragraph->textContent;
                        } else {
                            $description .= $paragraph->ownerDocument->saveHTML($paragraph);
                        }
                    }

                    $listItems[] = [
                        'title' => $title,
                        'description' => $description
                    ];
                }
            }
        }

        // Erzeuge die serialisierten Daten für das Listen-Element
        $serializedData = [];
        $subFields = $fieldConfig['fields'] ?? [];

        for ($i = 0; $i < min(count($listItems), $maxItems); $i++) {
            $item = [];

            // Befülle alle konfigurierten Unterfelder
            foreach ($subFields as $subFieldName => $subFieldConfig) {
                $value = null;

                switch ($subFieldName) {
                    case 'title':
                        $value = $listItems[$i]['title'] ?? ('Item ' . ($i + 1));
                        break;
                    case 'description':
                    case 'text':
                    case 'content':
                        $value = $listItems[$i]['description'] ?? '';
                        break;
                    case 'link':
                        // Fallback-Link zur Hauptseite
                        $value = '{{link_url::1}}';
                        break;
                    case 'image':
                        // Hier kann kein Bild automatisch generiert werden
                        // Fallback auf ein Standard-Bild, falls bekannt
                        $value = null;
                        break;
                    default:
                        $value = null;
                }

                if ($value !== null) {
                    $item[$subFieldName] = $value;
                }
            }

            $serializedData[] = $item;
        }

        // Serialisiere die Daten und speichere sie im Element
        $element->$fieldName = serialize($serializedData);
    }

    /**
     * Findet das am besten geeignete Feld für den Hauptinhalt
     */
    private function findMainContentField(array $fields): ?string
    {
        // Prioritätsreihenfolge für Felder
        $priorities = ['text', 'description', 'content', 'richtext', 'textarea'];

        foreach ($priorities as $fieldName) {
            if (isset($fields[$fieldName])) {
                return $fieldName;
            }
        }

        // Suche nach einem Feld mit einem Typ, der Text aufnehmen kann
        foreach ($fields as $fieldName => $fieldConfig) {
            if (isset($fieldConfig['inputType']) && in_array($fieldConfig['inputType'], ['text', 'textarea', 'markdown', 'rsce_html'])) {
                return $fieldName;
            }
        }

        return null;
    }

}
