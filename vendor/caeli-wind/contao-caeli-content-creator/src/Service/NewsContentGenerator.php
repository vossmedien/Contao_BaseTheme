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
use Psr\Log\LoggerInterface;

class NewsContentGenerator
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        private readonly string $projectDir
    ) {
    }

    /**
     * Creates a new news article using Contao Models.
     */
    public function createNewsArticle(
        int $archiveId,
        string $title,
        string $teaser,
        string $content,
        string $tags,
        string $elementType,
        string $pageTitle = '',
        string $description = ''
    ): int {
        $this->framework->initialize();

        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        $newsAdapter = $this->framework->getAdapter(NewsModel::class);

        // Generate and ensure unique alias
        $alias = $stringUtilAdapter->standardize($title);
        $originalAlias = $alias;
        $counter = 1;
        while ($newsAdapter->countBy(['alias=? AND pid=?'], [$alias, $archiveId]) > 0) {
            $alias = $originalAlias . '-' . $counter++;
            $this->logger->debug("Alias conflict for '{$originalAlias}', trying new alias: {$alias}");
        }

        // Create news article using NewsModel
        $news = new NewsModel();
        $news->pid = $archiveId;
        $news->headline = $title;
        $news->alias = $alias;
        $news->teaser = $teaser;
        $news->published = '1';
        $news->source = 'default';
        $news->date = time();
        $news->time = time();
        $news->tstamp = time();
        $news->tags = $tags;

        // SEO-Daten hinzufügen
        if (!empty($pageTitle)) {
            $news->pageTitle = $pageTitle;
        } else {
            // Fallback: Haupt-Titel als pageTitle, falls keiner generiert wurde
            $news->pageTitle = $title;
        }
        if (!empty($description)) {
            $news->description = $description;
        }
        // Optional: Wenn description leer ist, Fallback auf Teaser?
        // elseif (!empty($teaser)) {
        //    $news->description = $teaser;
        // }

        $news->save();

        $newsId = (int) $news->id;
        $this->logger->info("News article created", ['id' => $newsId, 'title' => $title, 'alias' => $alias]);

        // Create content element using ContentModel
        $contentElement = new ContentModel();
        $contentElement->pid = $newsId;
        $contentElement->ptable = 'tl_news';
        $contentElement->type = $elementType;
        $contentElement->tstamp = time();

        if ($elementType === 'text') {
            $contentElement->text = $content;
            $contentElement->save();
            $this->logger->info("Text content element created", ['id' => $contentElement->id, 'news_id' => $newsId]);
        } elseif ($elementType === 'html') {
            $contentElement->html = $content;
            $contentElement->save();
            $this->logger->info("HTML content element created", ['id' => $contentElement->id, 'news_id' => $newsId]);
        } elseif (str_starts_with($elementType, 'rsce_')) {
            $this->fillRockSolidElement($contentElement, $elementType, $title, $content); // Pass the model instance
            $contentElement->save();
            $this->logger->info("RSCE content element created", ['id' => $contentElement->id, 'type' => $elementType, 'news_id' => $newsId]);
        } else {
            $this->logger->warning("Unsupported element type, no content element created.", ['type' => $elementType, 'news_id' => $newsId]);
        }

        return $newsId;
    }

    /**
     * Converts HTML content to an array of list items or paragraphs.
     */
    private function convertContentToListItems(string $htmlContent): array
    {
        if (empty(trim($htmlContent))) {
            return [];
        }

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $htmlContent . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!$loaded || !empty($errors)) {
            $this->logger->warning('Failed to parse HTML in convertContentToListItems.', [
                'html_snippet' => substr($htmlContent, 0, 100),
                'errors' => array_map(fn($e) => trim($e->message), $errors)
            ]);
            return []; // Return empty on parsing failure
        }

        $items = [];
        $xpath = new \DOMXPath($dom);

        $listNodes = $xpath->query('.//li');
        if ($listNodes && $listNodes->length > 0) {
            foreach ($listNodes as $node) {
                $items[] = trim($node->nodeValue);
            }
        } else {
            $paragraphNodes = $xpath->query('.//p');
            if ($paragraphNodes && $paragraphNodes->length > 0) {
                foreach ($paragraphNodes as $node) {
                    $items[] = trim($node->nodeValue);
                }
            }
        }

        if (empty($items)) {
             $textOnly = strip_tags($htmlContent);
             $sentences = preg_split('/(?<=[.!?])\\s+/', $textOnly, -1, PREG_SPLIT_NO_EMPTY);
             $items = array_filter(array_map('trim', $sentences));
        }

        return array_filter($items);
    }

    /**
     * Fills a RockSolid Custom Element based on its configuration.
     */
    private function fillRockSolidElement(ContentModel $element, string $type, string $title, string $content): void
    {
        $elementName = substr($type, 5);
        $this->logger->debug("Attempting to fill RSCE '{$elementName}'");

        $configPaths = [
            $this->projectDir . '/templates/rsce/' . $elementName . '/config.php',
            $this->projectDir . '/templates/rsce_' . $elementName . '_config.php',
            $this->projectDir . '/templates/' . $elementName . '_config.php',
        ];

        $config = null;
        foreach ($configPaths as $path) {
            if (file_exists($path)) {
                try {
                    $config = include $path;
                    if (is_array($config) && isset($config['fields'])) {
                        $this->logger->info("Loaded RSCE config for '{$elementName}' from: {$path}");
                        break;
                    } else {
                         $this->logger->warning("Invalid RSCE config file (missing 'fields'): {$path}");
                         $config = null;
                    }
                } catch (\Throwable $e) {
                    $this->logger->error("Error loading RSCE config from '{$path}': " . $e->getMessage(), ['exception' => $e]);
                    $config = null;
                }
            }
        }

        if (!$config) {
            $this->logger->warning("No valid RSCE config found for '{$elementName}'. Falling back to 'text' field.", ['tried_paths' => $configPaths]);
            if (isset($element->current()->all()['text'])) {
                 $element->text = $content;
            }
            return;
        }

        // --- Basic Field Population ---
        $fields = $config['fields'] ?? [];
        $this->logger->debug("Available fields in RSCE '{$elementName}': " . implode(", ", array_keys($fields)));

        // Populate headline if available
        if (isset($fields['headline']) && isset($element->current()->all()['headline'])) {
            $element->headline = $title; // Defaulting to news title
        }
        if (isset($fields['headline_type']) && isset($element->current()->all()['headline_type'])) {
             $element->headline_type = 'h2'; // Defaulting to H2
        }

        // Populate text field if available
        if (isset($fields['text']) && isset($element->current()->all()['text'])) {
             $element->text = $content; // Defaulting to full content
        }

        // Populate list field if available
        if (isset($fields['items']) && ($fields['items']['inputType'] ?? null) === 'listWizard' && isset($element->current()->all()['items'])) {
             $listItems = $this->convertContentToListItems($content);
             if (!empty($listItems)) {
                 $element->items = serialize($listItems);
                 $this->logger->debug("Populated 'items' listWizard field.", ['count' => count($listItems)]);
             }
        }

        // --- Add more complex/specific field population logic here ---
        // Example: Image field (requires finding FilesModel by path/URL)
        // Example: Link field (requires creating internal/external link)

        $this->logger->debug("Finished populating RSCE '{$elementName}'");
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
 
