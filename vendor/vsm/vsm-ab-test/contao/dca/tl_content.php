<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

// Add fields to tl_content
$GLOBALS['TL_DCA']['tl_content']['fields']['enableAbTest'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['enableAbTest'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_content']['fields']['abTestVariant'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['abTestVariant'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['Vsm\VsmAbTest\EventListener\DataContainerListener', 'getAbTestVariantOptions'],
    'eval' => [
        'includeBlankOption' => true,
        'tl_class' => 'w50'
    ],
    'sql' => "varchar(32) NOT NULL default ''"
];

// Add selector for subpalette
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][] = 'enableAbTest';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['enableAbTest'] = 'abTestVariant';

// Add fields to standard content element palettes (nicht RockSolid)
foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $key => $palette) {
    // Skip special palettes und RockSolid elements
    if ($key === '__selector__' || empty($palette) || !is_string($palette) || strpos($key, 'rsce_') === 0) {
        continue;
    }
    
    // Add A/B test fields vor expert section
    if (strpos($palette, '{expert_legend') !== false) {
        $GLOBALS['TL_DCA']['tl_content']['palettes'][$key] = str_replace(
            '{expert_legend',
            '{abtest_legend},enableAbTest;{expert_legend',
            $palette
        );
    } else {
        // Fallback: Am Ende hinzufügen für andere Standard-Elemente
        $GLOBALS['TL_DCA']['tl_content']['palettes'][$key] .= ';{abtest_legend},enableAbTest';
    }
} 