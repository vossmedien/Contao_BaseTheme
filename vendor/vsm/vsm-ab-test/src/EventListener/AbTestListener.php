<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

namespace Vsm\VsmAbTest\EventListener;

use Vsm\VsmAbTest\Service\AbTestManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\PageModel;
use Contao\ModuleModel;

class AbTestListener
{
    private AbTestManager $abTestManager;

    public function __construct(AbTestManager $abTestManager)
    {
        $this->abTestManager = $abTestManager;
    }

    /**
     * Hook: getContentElement
     * Wird vor der Ausgabe eines Inhaltselements aufgerufen
     */
    #[AsHook('getContentElement')]
    public function onGetContentElement(ContentModel $element, string $buffer): string
    {
        // Wenn A/B Test aktiviert ist und Element nicht angezeigt werden soll
        if ($element->enableAbTest && $element->abTestVariant) {
            $page = $this->getCurrentPage();
            if ($page) {
                $filteredElements = $this->abTestManager->filterContentElements([$element], $page);
                
                // Wenn Element gefiltert wurde (nicht in Array), leeren String zurückgeben
                if (empty($filteredElements)) {
                    return '';
                }
            }
        }
        
        return $buffer;
    }

    /**
     * Hook: getArticle
     * Wird vor der Ausgabe eines Artikels aufgerufen
     */
    #[AsHook('getArticle')]
    public function onGetArticle(ArticleModel $article): ?ArticleModel
    {
        // Wenn A/B Test aktiviert ist
        if ($article->enableAbTest && $article->abTestVariant) {
            $page = $this->getCurrentPage();
            if ($page) {
                $filteredArticles = $this->abTestManager->filterArticles([$article], $page);
                
                // Wenn Artikel gefiltert wurde (nicht in Array), null zurückgeben
                if (empty($filteredArticles)) {
                    return null;
                }
            }
        }
        
        return $article;
    }

    /**
     * Hook: replaceInsertTags
     * Ermöglicht Insert Tags für A/B Test Info
     */
    #[AsHook('replaceInsertTags')]
    public function onReplaceInsertTags(string $tag): string|false
    {
        $chunks = explode('::', $tag);
        
        if ($chunks[0] !== 'abtest') {
            return false;
        }

        $page = $this->getCurrentPage();
        if (!$page) {
            return '';
        }

        switch ($chunks[1] ?? '') {
            case 'variant':
                $type = $chunks[2] ?? 'content';
                return $this->abTestManager->getSelectedVariantForPage($page, $type);
                
            case 'active':
                return $this->abTestManager->hasActiveAbTests($page) ? '1' : '0';
                
            case 'reset':
                // Reset Session für diese Seite
                global $objPage;
                if ($objPage) {
                    $request = \Contao\System::getContainer()->get('request_stack')->getCurrentRequest();
                    if ($request) {
                        $session = $request->getSession();
                        $type = $chunks[2] ?? 'content';
                        $cacheKey = $type . '_' . $objPage->id;
                        $session->remove('ab_test_' . $cacheKey);
                        return 'A/B Test Session zurückgesetzt für Typ: ' . $type;
                    }
                }
                return '';
                
            case 'debug':
                // Debug Info ausgeben
                $type = $chunks[2] ?? 'content';
                $variant = $this->abTestManager->getSelectedVariantForPage($page, $type);
                $active = $this->abTestManager->hasActiveAbTests($page);
                return sprintf(
                    '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">
                        <strong>A/B Test Debug Info:</strong><br>
                        Seite ID: %d<br>
                        Typ: %s<br>
                        Aktuelle Variante: %s<br>
                        Tests aktiv: %s<br>
                        <a href="?abtest_reset=1">Session zurücksetzen</a>
                    </div>',
                    $page->id,
                    $type,
                    $variant ?: 'keine',
                    $active ? 'Ja' : 'Nein'
                );
                
            default:
                return '';
        }
    }

    /**
     * Hook: getFrontendModule
     * Wird vor der Ausgabe eines Moduls aufgerufen
     */
    #[AsHook('getFrontendModule')]
    public function onGetFrontendModule(ModuleModel $module, string $buffer): string
    {
        // Wenn A/B Test aktiviert ist
        if ($module->enableAbTest && $module->abTestVariant) {
            $page = $this->getCurrentPage();
            if ($page) {
                $filteredModules = $this->abTestManager->filterModules([$module], $page);
                
                // Wenn Modul gefiltert wurde (nicht in Array), leeren String zurückgeben
                if (empty($filteredModules)) {
                    return '';
                }
            }
        }
        
        return $buffer;
    }

    /**
     * Hook: getPageIdFromUrl
     * Wird beim Aufbau der Navigation aufgerufen
     */
    #[AsHook('getPageIdFromUrl')]
    public function onGetPageIdFromUrl(array $fragments): ?array
    {
        // Hier könnten wir A/B Test Logik für URL-Handling einbauen
        // Erstmal nur durchreichen
        return null; // null = kein Eingriff
    }

    /**
     * Ermittelt die aktuelle Seite
     */
    private function getCurrentPage(): ?PageModel
    {
        global $objPage;
        
        if ($objPage instanceof PageModel) {
            return $objPage;
        }
        
        return null;
    }
} 