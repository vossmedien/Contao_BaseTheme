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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\NewsModel;
use Contao\StringUtil;

class DuplicateChecker
{
    /**
     * @var ContaoFramework
     */
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    /**
     * Prüft, ob es möglicherweise Duplikate für den gegebenen Titel und Teaser gibt.
     * 
     * @param int $archiveId ID des News-Archivs
     * @param string $title Titel des zu prüfenden Beitrags
     * @param string $teaser Teaser des zu prüfenden Beitrags
     * @return array Ein Array mit möglichen Duplikaten
     */
    public function checkForDuplicates(int $archiveId, string $title, string $teaser): array
    {
        $this->framework->initialize();
        
        $newsAdapter = $this->framework->getAdapter(NewsModel::class);
        $stringUtilAdapter = $this->framework->getAdapter(StringUtil::class);
        
        // Alle News aus diesem Archiv abrufen
        $newsItems = $newsAdapter->findPublishedByPid($archiveId);
        
        if (null === $newsItems) {
            return [];
        }
        
        $duplicates = [];
        $titleWords = $this->extractKeywords($title);
        $teaserWords = $this->extractKeywords($teaser);
        
        foreach ($newsItems as $item) {
            $similarity = $this->calculateSimilarity(
                $titleWords, 
                $this->extractKeywords($item->headline)
            );
            
            $teaserSimilarity = 0;
            if (!empty($item->teaser)) {
                $teaserSimilarity = $this->calculateSimilarity(
                    $teaserWords,
                    $this->extractKeywords($item->teaser)
                );
            }
            
            // Wenn Titel mehr als 70% ähnlich oder Teaser mehr als 60% ähnlich
            if ($similarity > 0.7 || $teaserSimilarity > 0.6) {
                $duplicates[] = [
                    'id' => $item->id,
                    'headline' => $item->headline,
                    'teaser' => $item->teaser,
                    'date' => date('d.m.Y', (int)$item->date),
                    'titleSimilarity' => round($similarity * 100, 1),
                    'teaserSimilarity' => round($teaserSimilarity * 100, 1)
                ];
            }
        }
        
        return $duplicates;
    }
    
    /**
     * Extrahiert relevante Keywords aus einem Text
     */
    private function extractKeywords(string $text): array
    {
        // Text bereinigen und in Wörter aufteilen
        $text = strtolower(strip_tags($text));
        $words = preg_split('/\W+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Stoppwörter entfernen (häufige deutsche Wörter, die keine Relevanz haben)
        $stopWords = ['der', 'die', 'das', 'ein', 'eine', 'und', 'oder', 'aber', 'in', 'mit', 'für', 'von', 'zu', 'auf', 'ist', 'sind', 'war', 'wird', 'werden', 'hat', 'haben'];
        $words = array_diff($words, $stopWords);
        
        // Nur Wörter behalten, die mindestens 3 Zeichen lang sind
        $words = array_filter($words, function($word) {
            return mb_strlen($word) >= 3;
        });
        
        return array_values($words);
    }
    
    /**
     * Berechnet die Ähnlichkeit zwischen zwei Keyword-Arrays
     * Wert zwischen 0 (keine Ähnlichkeit) und 1 (identisch)
     */
    private function calculateSimilarity(array $keywords1, array $keywords2): float
    {
        if (empty($keywords1) || empty($keywords2)) {
            return 0;
        }
        
        // Anzahl der übereinstimmenden Keywords
        $common = count(array_intersect($keywords1, $keywords2));
        
        // Jaccard-Ähnlichkeit: Anzahl gemeinsamer Elemente / Anzahl aller einzigartigen Elemente
        $union = count(array_unique(array_merge($keywords1, $keywords2)));
        
        return $union > 0 ? $common / $union : 0;
    }
} 