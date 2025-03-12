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
use Contao\System;

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
        
        // Protokollierung für Debugging
        $logDir = $this->framework->getAdapter(System::class)->getContainer()->getParameter('kernel.logs_dir');
        file_put_contents($logDir . '/duplicate-checker.log', "Prüfe auf Duplikate für Titel: {$title}\n", FILE_APPEND);
        
        // Alle News aus diesem Archiv abrufen
        $newsItems = $newsAdapter->findPublishedByPid($archiveId);
        
        if (null === $newsItems) {
            return [];
        }
        
        $duplicates = [];
        $titleWords = $this->extractKeywords($title);
        $teaserWords = $this->extractKeywords($teaser);
        
        // Normalisierte Werte für Vergleiche
        $normalizedTitle = $this->normalizeText($title);
        $normalizedTitleWords = explode(' ', $normalizedTitle);
        
        // Kernthemen des Titels identifizieren (substantielle Worte)
        $titleThemes = $this->extractCoreThemes($title);
        file_put_contents($logDir . '/duplicate-checker.log', "Erkannte Kernthemen: " . implode(', ', $titleThemes) . "\n", FILE_APPEND);
        
        foreach ($newsItems as $item) {
            $isSignificantDuplicate = false;
            $duplicateType = '';
            $similarityReason = [];
            
            // 1. Exakte Titelübereinstimmung
            $normalizedItemTitle = $this->normalizeText($item->headline);
            if ($normalizedTitle === $normalizedItemTitle) {
                $isSignificantDuplicate = true;
                $duplicateType = 'exact_match';
                $similarityReason[] = 'Exakt gleicher Titel';
            }
            
            // 2. Partielle Titelübereinstimmung (Titel beginnt mit oder enthält den anderen)
            else if (str_starts_with($normalizedTitle, substr($normalizedItemTitle, 0, 20)) || 
                     str_starts_with($normalizedItemTitle, substr($normalizedTitle, 0, 20))) {
                $isSignificantDuplicate = true;
                $duplicateType = 'partial_match';
                $similarityReason[] = 'Titel beginnen sehr ähnlich';
            }
            
            // 3. Übereinstimmende Hauptthemen im Titel
            $itemThemes = $this->extractCoreThemes($item->headline);
            $commonThemes = array_intersect($titleThemes, $itemThemes);
            
            if (count($commonThemes) >= 2 && count($titleThemes) >= 2) {
                $themeOverlapPercentage = count($commonThemes) / count($titleThemes) * 100;
                if ($themeOverlapPercentage >= 50) {
                    $isSignificantDuplicate = true;
                    $duplicateType = 'theme_match';
                    $similarityReason[] = 'Übereinstimmende Hauptthemen: ' . implode(', ', $commonThemes);
                }
            }
            
            // 4. Keyword-basierte Ähnlichkeit (traditioneller Ansatz)
            $similarity = $this->calculateSimilarity(
                $titleWords, 
                $this->extractKeywords($item->headline)
            );
            
            $teaserSimilarity = 0;
            if (!empty($item->teaser) && !empty($teaser)) {
                $teaserSimilarity = $this->calculateSimilarity(
                    $teaserWords,
                    $this->extractKeywords($item->teaser)
                );
            }
            
            // Niedrigere Schwellen für höhere Sensitivität
            if ($similarity > 0.45 || $teaserSimilarity > 0.4) {
                $isSignificantDuplicate = true;
                $duplicateType = 'keyword_match';
                $similarityReason[] = 'Hohe Keyword-Übereinstimmung';
            }
            
            // 5. Längere gemeinsame Wortsequenzen prüfen
            $longestCommonSequence = $this->findLongestCommonWordSequence($normalizedTitleWords, explode(' ', $normalizedItemTitle));
            if ($longestCommonSequence >= 3) {
                $isSignificantDuplicate = true;
                $duplicateType = 'sequence_match';
                $similarityReason[] = 'Gemeinsame Wortsequenz von ' . $longestCommonSequence . ' Wörtern';
            }
            
            // Wenn eine signifikante Ähnlichkeit gefunden wurde
            if ($isSignificantDuplicate) {
                $roundedSimilarity = round($similarity * 100, 1);
                $roundedTeaserSimilarity = round($teaserSimilarity * 100, 1);
                
                $duplicates[] = [
                    'id' => $item->id,
                    'headline' => $item->headline,
                    'teaser' => $item->teaser,
                    'date' => date('d.m.Y', (int)$item->date),
                    'titleSimilarity' => $roundedSimilarity,
                    'teaserSimilarity' => $roundedTeaserSimilarity,
                    'duplicateType' => $duplicateType,
                    'reason' => implode('; ', $similarityReason),
                    'exactMatch' => ($duplicateType === 'exact_match')
                ];
                
                file_put_contents($logDir . '/duplicate-checker.log', 
                    "DUPLIKAT GEFUNDEN: \"{$item->headline}\" - Typ: {$duplicateType}\n" .
                    "Grund: " . implode('; ', $similarityReason) . "\n" .
                    "Ähnlichkeit: Titel {$roundedSimilarity}%, Teaser {$roundedTeaserSimilarity}%\n", 
                    FILE_APPEND
                );
            }
        }
        
        file_put_contents($logDir . '/duplicate-checker.log', "Insgesamt " . count($duplicates) . " Duplikate gefunden.\n\n", FILE_APPEND);
        
        return $duplicates;
    }
    
    /**
     * Extrahiert Kernthemen aus einem Titel
     * Identifiziert die substantiellen Worte, die das Hauptthema eines Titels darstellen
     */
    private function extractCoreThemes(string $text): array
    {
        $normalizedText = $this->normalizeText($text);
        $words = explode(' ', $normalizedText);
        
        // Ignoriere sehr kurze oder allgemeine Worte
        $filteredWords = array_filter($words, function($word) {
            // Liste häufiger Verbindungs- und Allgemeinwörter
            $commonWords = ['und', 'oder', 'für', 'mit', 'der', 'die', 'das', 'eine', 'ein', 'ist', 'sind', 'von', 'zu', 'in', 'bei', 'durch', 'wie', 'was', 'wer', 'wo', 'als'];
            
            return mb_strlen($word) > 4 && !in_array($word, $commonWords);
        });
        
        // Suche nach spezifischen Domänenbegriffen, die besonders relevant sind
        $domainSpecificTerms = ['wind', 'energie', 'caeli', 'windenergie', 'energiewende', 'windkraft', 'windpark', 'offshore', 'onshore', 'nachhaltig', 'erneuerbar'];
        
        $themes = [];
        foreach ($filteredWords as $word) {
            // Priorisiere domänenspezifische Begriffe
            foreach ($domainSpecificTerms as $term) {
                if (strpos($word, $term) !== false) {
                    $themes[] = $word;
                    continue 2; // Zum nächsten Wort
                }
            }
            
            // Füge auch andere relevante Worte hinzu
            if (mb_strlen($word) > 5) {
                $themes[] = $word;
            }
        }
        
        return array_unique($themes);
    }
    
    /**
     * Findet die längste gemeinsame Wortsequenz zwischen zwei Texten
     */
    private function findLongestCommonWordSequence(array $words1, array $words2): int
    {
        $longest = 0;
        $len1 = count($words1);
        $len2 = count($words2);
        
        // Dynamische Programmierung für das längste gemeinsame Subsequenz-Problem
        $dp = array_fill(0, $len1 + 1, array_fill(0, $len2 + 1, 0));
        
        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                if ($words1[$i-1] === $words2[$j-1]) {
                    $dp[$i][$j] = $dp[$i-1][$j-1] + 1;
                    $longest = max($longest, $dp[$i][$j]);
                }
            }
        }
        
        return $longest;
    }
    
    /**
     * Normalisiert einen Text für exakten Stringvergleich
     */
    private function normalizeText(string $text): string
    {
        // HTML-Tags entfernen, Leerzeichen normalisieren, zu Kleinbuchstaben
        $text = strtolower(strip_tags($text));
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Sonderzeichen entfernen und Umlaute normalisieren
        $text = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        
        return $text;
    }
    
    /**
     * Extrahiert relevante Keywords aus einem Text
     */
    private function extractKeywords(string $text): array
    {
        // Text bereinigen und in Wörter aufteilen
        $text = strtolower(strip_tags($text));
        $words = preg_split('/\W+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        
        // Erweiterte Liste deutscher Stoppwörter
        $stopWords = [
            'der', 'die', 'das', 'ein', 'eine', 'und', 'oder', 'aber', 'in', 'mit', 'für', 'von', 'zu', 'auf', 
            'ist', 'sind', 'war', 'wird', 'werden', 'hat', 'haben', 'als', 'an', 'am', 'bei', 'durch', 'aus', 
            'nach', 'vor', 'hinter', 'über', 'unter', 'zwischen', 'wegen', 'ohne', 'um', 'zum', 'zur', 'sehr',
            'wie', 'so', 'zum', 'zur', 'bis', 'dass', 'daß', 'weil', 'wenn', 'ob', 'obwohl', 'denn', 'damit'
        ];
        $words = array_diff($words, $stopWords);
        
        // Nur Wörter behalten, die mindestens 2 Zeichen lang sind
        $words = array_filter($words, function($word) {
            return mb_strlen($word) >= 2;
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
        
        // Verbesserte Berechnung: Gewichtung nach Wortlänge und Häufigkeit
        $weightedIntersection = 0;
        $weightedUnion = 0;
        
        // Häufigkeiten der Wörter zählen
        $words1Count = array_count_values($keywords1);
        $words2Count = array_count_values($keywords2);
        
        // Alle einzigartigen Wörter
        $allWords = array_unique(array_merge(array_keys($words1Count), array_keys($words2Count)));
        
        foreach ($allWords as $word) {
            $count1 = $words1Count[$word] ?? 0;
            $count2 = $words2Count[$word] ?? 0;
            
            // Wortgewicht basierend auf Länge (längere Wörter sind wichtiger)
            $wordWeight = mb_strlen($word) / 5; // Normalisiere auf ca. 1 für ein 5-Buchstaben-Wort
            $wordWeight = min($wordWeight, 2); // Cap bei 2 für sehr lange Wörter
            
            // Minimum der Häufigkeiten für Schnittmenge
            $intersectionCount = min($count1, $count2) * $wordWeight;
            $weightedIntersection += $intersectionCount;
            
            // Maximum der Häufigkeiten für Vereinigungsmenge
            $unionCount = max($count1, $count2) * $wordWeight;
            $weightedUnion += $unionCount;
        }
        
        // Jaccard-Ähnlichkeit mit Gewichtung
        return $weightedUnion > 0 ? $weightedIntersection / $weightedUnion : 0;
    }
} 