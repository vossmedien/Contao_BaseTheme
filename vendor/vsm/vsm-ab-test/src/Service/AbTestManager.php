<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

namespace Vsm\VsmAbTest\Service;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\RequestStack;

class AbTestManager
{
    private RequestStack $requestStack;
    private array $selectedVariants = [];

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * Filtert Inhaltselemente basierend auf A/B Test Logik
     */
    public function filterContentElements(array $contentElements, PageModel $page): array
    {
        $abTestElements = [];
        $normalElements = [];

        // Trenne A/B Test Elemente von normalen Elementen
        foreach ($contentElements as $element) {
            if ($element->abTestVariant) {
                $abTestElements[] = $element;
            } else {
                $normalElements[] = $element;
            }
        }

        // Wenn keine A/B Test Elemente vorhanden sind, alle zurückgeben
        if (empty($abTestElements)) {
            return $contentElements;
        }

        // Bestimme ausgewählte Variante für diese Seite
        $selectedVariant = $this->getSelectedVariantForPage($page, 'content');
        
        // Filtere A/B Test Elemente nach ausgewählter Variante
        $filteredAbTestElements = array_filter($abTestElements, function($element) use ($selectedVariant) {
            return $element->abTestVariant === $selectedVariant;
        });

        // Kombiniere normale Elemente mit gefilterten A/B Test Elementen
        return array_merge($normalElements, $filteredAbTestElements);
    }

    /**
     * Filtert Artikel basierend auf A/B Test Logik
     */
    public function filterArticles(array $articles, PageModel $page): array
    {
        $abTestArticles = [];
        $normalArticles = [];

        // Trenne A/B Test Artikel von normalen Artikeln
        foreach ($articles as $article) {
            if ($article->enableAbTest && $article->abTestVariant) {
                $abTestArticles[] = $article;
            } else {
                $normalArticles[] = $article;
            }
        }

        // Wenn keine A/B Test Artikel vorhanden sind, alle zurückgeben
        if (empty($abTestArticles)) {
            return $articles;
        }

        // Bestimme ausgewählte Variante für diese Seite
        $selectedVariant = $this->getSelectedVariantForPage($page, 'article');
        
        // Filtere A/B Test Artikel nach ausgewählter Variante
        $filteredAbTestArticles = array_filter($abTestArticles, function($article) use ($selectedVariant) {
            return $article->abTestVariant === $selectedVariant;
        });

        // Kombiniere normale Artikel mit gefilterten A/B Test Artikeln
        return array_merge($normalArticles, $filteredAbTestArticles);
    }

    /**
     * Bestimmt die ausgewählte Variante für eine Seite basierend auf Session/Cookie
     */
    public function getSelectedVariantForPage(PageModel $page, string $type): string
    {
        $pageId = $page->id;
        $cacheKey = $type . '_' . $pageId;

        // Prüfe ob bereits eine Variante für diese Seite ausgewählt wurde
        if (isset($this->selectedVariants[$cacheKey])) {
            return $this->selectedVariants[$cacheKey];
        }

        // Hole verfügbare Varianten für diese Seite
        $availableVariants = $this->getAvailableVariantsForPage($page, $type);
        
        if (empty($availableVariants)) {
            return '';
        }

        // Session für Konsistenz verwenden
        $request = $this->requestStack->getCurrentRequest();
        $session = $request?->getSession();
        
        if ($session && $session->has('ab_test_' . $cacheKey)) {
            $storedVariant = $session->get('ab_test_' . $cacheKey);
            if (in_array($storedVariant, $availableVariants, true)) {
                $this->selectedVariants[$cacheKey] = $storedVariant;
                return $storedVariant;
            }
        }

        // Homogene Verteilung basierend auf Session-ID oder IP für Konsistenz
        $selectedVariant = $this->selectVariantHomogenously($availableVariants, $pageId, $type);
        
        // In Session speichern für Konsistenz
        if ($session) {
            $session->set('ab_test_' . $cacheKey, $selectedVariant);
        }
        
        $this->selectedVariants[$cacheKey] = $selectedVariant;
        
        return $selectedVariant;
    }

    /**
     * Wählt eine Variante homogen aus verfügbaren Varianten aus
     * Berücksichtigt nur tatsächlich vorhandene Tests
     */
    private function selectVariantHomogenously(array $availableVariants, int $pageId, string $type): string
    {
        if (empty($availableVariants)) {
            return '';
        }

        // Erstelle einen stabilen Hash basierend auf Session oder IP + Page + Type
        $request = $this->requestStack->getCurrentRequest();
        $session = $request?->getSession();
        
        // Fallbacks für stabile Identifikation
        $identifier = '';
        if ($session && $session->getId()) {
            $identifier = $session->getId();
        } elseif ($request && $request->getClientIp()) {
            $identifier = $request->getClientIp();
        } else {
            // Fallback auf Zufallsauswahl wenn keine stabile ID verfügbar
            return $availableVariants[array_rand($availableVariants)];
        }
        
        // Erstelle Hash für konsistente aber gleichmäßige Verteilung
        $hashInput = $identifier . '_' . $pageId . '_' . $type;
        $hash = crc32($hashInput);
        
        // Modulo für gleichmäßige Verteilung auf verfügbare Varianten
        $variantIndex = abs($hash) % count($availableVariants);
        
        return $availableVariants[$variantIndex];
    }

    /**
     * Ermittelt verfügbare Varianten für eine Seite
     */
    private function getAvailableVariantsForPage(PageModel $page, string $type): array
    {
        $variants = [];

        if ($type === 'content') {
            // Suche nach Inhaltselementen mit A/B Tests auf dieser Seite direkt
            $contentElements = ContentModel::findBy(['pid=?', 'ptable=?'], [$page->id, 'tl_page']);
            if ($contentElements) {
                foreach ($contentElements as $element) {
                    if ($element->abTestVariant) {
                        $variants[] = $element->abTestVariant;
                    }
                }
            }
            
            // Auch Artikel-Inhalte berücksichtigen
            $articles = ArticleModel::findBy('pid', $page->id);
            if ($articles) {
                foreach ($articles as $article) {
                    $articleContents = ContentModel::findBy(['pid=?', 'ptable=?'], [$article->id, 'tl_article']);
                    if ($articleContents) {
                        foreach ($articleContents as $element) {
                            if ($element->abTestVariant) {
                                $variants[] = $element->abTestVariant;
                            }
                        }
                    }
                }
            }
        } else {
            // Suche nach Artikeln mit A/B Tests auf dieser Seite
            $articles = ArticleModel::findBy('pid', $page->id);
            if ($articles) {
                foreach ($articles as $article) {
                    if ($article->enableAbTest && $article->abTestVariant) {
                        $variants[] = $article->abTestVariant;
                    }
                }
            }
        }

        return array_unique($variants);
    }

    /**
     * Überprüft ob A/B Tests auf der aktuellen Seite aktiv sind
     */
    public function hasActiveAbTests(PageModel $page): bool
    {
        return !empty($this->getAvailableVariantsForPage($page, 'content')) || 
               !empty($this->getAvailableVariantsForPage($page, 'article'));
    }

    /**
     * Filtert Module basierend auf A/B Test Logik
     */
    public function filterModules(array $modules, PageModel $page): array
    {
        $abTestModules = [];
        $normalModules = [];

        // Trenne A/B Test Module von normalen Modulen
        foreach ($modules as $module) {
            if ($module->enableAbTest && $module->abTestVariant) {
                $abTestModules[] = $module;
            } else {
                $normalModules[] = $module;
            }
        }

        // Wenn keine A/B Test Module vorhanden sind, alle zurückgeben
        if (empty($abTestModules)) {
            return $modules;
        }

        // Bestimme ausgewählte Variante für diese Seite
        $selectedVariant = $this->getSelectedVariantForPage($page, 'module');
        
        // Filtere A/B Test Module nach ausgewählter Variante
        $filteredAbTestModules = array_filter($abTestModules, function($module) use ($selectedVariant) {
            return $module->abTestVariant === $selectedVariant;
        });

        // Kombiniere normale Module mit gefilterten A/B Test Modulen
        return array_merge($normalModules, $filteredAbTestModules);
    }

    /**
     * Filtert Seiten basierend auf A/B Test Logik mit Gruppen-System
     */
    public function filterPages(array $pages, PageModel $currentPage): array
    {
        $abTestPages = [];
        $normalPages = [];
        $groupedPages = [];

        // Trenne A/B Test Seiten von normalen Seiten und gruppiere sie
        foreach ($pages as $page) {
            if ($page->enableAbTest && $page->abTestVariant && $page->abTestGroup) {
                $abTestPages[] = $page;
                $groupedPages[$page->abTestGroup][] = $page;
            } else {
                $normalPages[] = $page;
            }
        }

        // Wenn keine A/B Test Seiten vorhanden sind, alle zurückgeben
        if (empty($abTestPages)) {
            return $pages;
        }

        $filteredAbTestPages = [];

        // Für jede Gruppe eine Variante auswählen
        foreach ($groupedPages as $group => $groupPages) {
            $selectedVariant = $this->getSelectedVariantForPageGroup($group, $currentPage);
            
            // Nur Seiten der ausgewählten Variante hinzufügen
            foreach ($groupPages as $page) {
                if ($page->abTestVariant === $selectedVariant) {
                    $filteredAbTestPages[] = $page;
                }
            }
        }

        // Kombiniere normale Seiten mit gefilterten A/B Test Seiten
        return array_merge($normalPages, $filteredAbTestPages);
    }

    /**
     * Bestimmt die ausgewählte Variante für eine Seitengruppe
     */
    private function getSelectedVariantForPageGroup(string $group, PageModel $currentPage): string
    {
        $cacheKey = 'page_group_' . $group;

        // Prüfe ob bereits eine Variante für diese Gruppe ausgewählt wurde
        if (isset($this->selectedVariants[$cacheKey])) {
            return $this->selectedVariants[$cacheKey];
        }

        // Hole verfügbare Varianten für diese Gruppe
        $availableVariants = $this->getAvailableVariantsForPageGroup($group);
        
        if (empty($availableVariants)) {
            return '';
        }

        // Session für Konsistenz verwenden
        $request = $this->requestStack->getCurrentRequest();
        $session = $request?->getSession();
        
        if ($session && $session->has('ab_test_' . $cacheKey)) {
            $storedVariant = $session->get('ab_test_' . $cacheKey);
            if (in_array($storedVariant, $availableVariants, true)) {
                $this->selectedVariants[$cacheKey] = $storedVariant;
                return $storedVariant;
            }
        }

        // Homogene Verteilung für Seitengruppen
        $selectedVariant = $this->selectVariantHomogenously($availableVariants, crc32($group), 'page_group');
        
        // In Session speichern für Konsistenz
        if ($session) {
            $session->set('ab_test_' . $cacheKey, $selectedVariant);
        }
        
        $this->selectedVariants[$cacheKey] = $selectedVariant;
        
        return $selectedVariant;
    }

    /**
     * Ermittelt verfügbare Varianten für eine Seitengruppe
     */
    private function getAvailableVariantsForPageGroup(string $group): array
    {
        $variants = [];
        
        // Suche nach allen Seiten in dieser A/B Test Gruppe
        $db = \Database::getInstance();
        $result = $db->prepare("SELECT DISTINCT abTestVariant FROM tl_page WHERE enableAbTest=? AND abTestGroup=? AND published=?")
                     ->execute('1', $group, '1');
        
        while ($result->next()) {
            if ($result->abTestVariant) {
                $variants[] = $result->abTestVariant;
            }
        }

        return $variants;
    }
} 