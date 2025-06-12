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
use Contao\ContentModel;
use Contao\PageModel;

class ContentElementListener
{
    private AbTestManager $abTestManager;

    public function __construct(AbTestManager $abTestManager)
    {
        $this->abTestManager = $abTestManager;
    }

    /**
     * Hook: parseWidget - Wird vor der Template-Ausgabe aufgerufen
     */
    #[AsHook('parseWidget')]
    public function onParseWidget(string $buffer, object $widget): string
    {
        // Nur für Content Elemente relevant
        if (!$widget instanceof ContentModel) {
            return $buffer;
        }

        // Prüfe ob A/B Test aktiviert ist
        if (!$widget->enableAbTest || !$widget->abTestVariant) {
            return $buffer;
        }

        $page = $this->getCurrentPage();
        if (!$page) {
            return $buffer;
        }

        // Bestimme ob dieses Element angezeigt werden soll
        $selectedVariant = $this->abTestManager->getSelectedVariantForPage($page, 'content');
        
        if ($widget->abTestVariant !== $selectedVariant) {
            // Element soll nicht angezeigt werden
            return '';
        }

        // Optional: Debug-Info hinzufügen (nur für Backend-Benutzer)
        if ($this->isBackendUser()) {
            $variant = strtoupper(str_replace('test_', '', $widget->abTestVariant));
            $buffer = '<!-- A/B Test: Showing variant ' . $variant . ' -->' . PHP_EOL . $buffer;
        }

        return $buffer;
    }

    /**
     * Hook: parseTemplate - Alternative für Template-Parsing
     */
    #[AsHook('parseTemplate')]
    public function onParseTemplate(object $template): void
    {
        // Template-spezifische A/B Test Logik wenn nötig
        if (isset($template->abTestVariant) && $template->abTestVariant) {
            $template->abTestActive = true;
            $template->abTestVariantName = strtoupper(str_replace('test_', 'Test ', $template->abTestVariant));
        }
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

    /**
     * Prüft ob aktueller Benutzer Backend-Zugriff hat
     */
    private function isBackendUser(): bool
    {
        return defined('TL_MODE') && TL_MODE === 'BE';
    }
} 