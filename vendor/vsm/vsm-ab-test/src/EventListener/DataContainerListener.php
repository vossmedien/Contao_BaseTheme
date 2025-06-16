<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

namespace Vsm\VsmAbTest\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Message;

class DataContainerListener
{
    /**
     * Validierung für A/B Test Felder bei Inhaltselementen
     */
    #[AsCallback(table: 'tl_content', target: 'fields.enableAbTest.save')]
    public function onSaveContentAbTest($value, DataContainer $dc)
    {
        // Wenn A/B Test aktiviert wird, prüfe ob Variante ausgewählt wurde
        if ($value && !$dc->activeRecord?->abTestVariant) {
            Message::addInfo('Bitte wählen Sie eine Test-Variante aus, wenn A/B Tests aktiviert sind.');
        }
        
        return $value;
    }

    /**
     * Validierung für A/B Test Felder bei Artikeln
     */
    #[AsCallback(table: 'tl_article', target: 'fields.enableAbTest.save')]
    public function onSaveArticleAbTest($value, DataContainer $dc)
    {
        // Wenn A/B Test aktiviert wird, prüfe ob Variante ausgewählt wurde
        if ($value && !$dc->activeRecord?->abTestVariant) {
            Message::addInfo('Bitte wählen Sie eine Test-Variante aus, wenn A/B Tests aktiviert sind.');
        }
        
        return $value;
    }

    /**
     * Validierung für A/B Test Felder bei Modulen
     */
    #[AsCallback(table: 'tl_module', target: 'fields.enableAbTest.save')]
    public function onSaveModuleAbTest($value, DataContainer $dc)
    {
        // Wenn A/B Test aktiviert wird, prüfe ob Variante ausgewählt wurde
        if ($value && !$dc->activeRecord?->abTestVariant) {
            Message::addInfo('Bitte wählen Sie eine Test-Variante aus, wenn A/B Tests aktiviert sind.');
        }
        
        return $value;
    }

    /**
     * Validierung für A/B Test Felder bei Seiten
     */
    #[AsCallback(table: 'tl_page', target: 'fields.enableAbTest.save')]
    public function onSavePageAbTest($value, DataContainer $dc)
    {
        // Wenn A/B Test aktiviert wird, prüfe ob Variante ausgewählt wurde
        if ($value && !$dc->activeRecord?->abTestVariant) {
            Message::addInfo('Bitte wählen Sie eine Test-Variante aus, wenn A/B Tests aktiviert sind.');
        }
        
        return $value;
    }

    /**
     * Options callback für Test-Varianten - erweitert für alle Tabellen
     */
    #[AsCallback(table: 'tl_content', target: 'fields.abTestVariant.options')]
    #[AsCallback(table: 'tl_article', target: 'fields.abTestVariant.options')]
    #[AsCallback(table: 'tl_module', target: 'fields.abTestVariant.options')]
    #[AsCallback(table: 'tl_page', target: 'fields.abTestVariant.options')]
    public function getAbTestVariantOptions(): array
    {
        return [
            'test_a' => 'Test A',
            'test_b' => 'Test B', 
            'test_c' => 'Test C'
        ];
    }

    /**
     * Label callback für bessere Anzeige im Backend
     */
    #[AsCallback(table: 'tl_content', target: 'list.label.label')]
    public function addAbTestInfoToContentLabel($row, string $label): string
    {
        if ($row['enableAbTest'] && $row['abTestVariant']) {
            $variant = strtoupper(str_replace('test_', '', $row['abTestVariant']));
            $label .= ' <span style="color: #b3d4fc; font-weight: bold;">[A/B Test: ' . $variant . ']</span>';
        }
        
        return $label;
    }

    /**
     * Label callback für Artikel
     */
    #[AsCallback(table: 'tl_article', target: 'list.label.label')]
    public function addAbTestInfoToArticleLabel($row, string $label): string
    {
        if ($row['enableAbTest'] && $row['abTestVariant']) {
            $variant = strtoupper(str_replace('test_', '', $row['abTestVariant']));
            $label .= ' <span style="color: #b3d4fc; font-weight: bold;">[A/B Test: ' . $variant . ']</span>';
        }
        
        return $label;
    }

    /**
     * Label callback für Module
     */
    #[AsCallback(table: 'tl_module', target: 'list.label.label')]
    public function addAbTestInfoToModuleLabel($row, string $label): string
    {
        if ($row['enableAbTest'] && $row['abTestVariant']) {
            $variant = strtoupper(str_replace('test_', '', $row['abTestVariant']));
            $label .= ' <span style="color: #b3d4fc; font-weight: bold;">[A/B Test: ' . $variant . ']</span>';
        }
        
        return $label;
    }

    /**
     * Label callback für Seiten (Seitenstruktur)
     * Hinweis: tl_page verwendet ein spezielles Tree-System, daher wird hier kein Label-Callback verwendet
     * A/B Test Informationen werden direkt in der Seitenstruktur angezeigt
     */

} 