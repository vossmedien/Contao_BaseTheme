<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

// Add fields to tl_page
$GLOBALS['TL_DCA']['tl_page']['fields']['enableAbTest'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['enableAbTest'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_page']['fields']['abTestGroup'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['abTestGroup'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'maxlength' => 64,
        'tl_class' => 'w50'
    ],
    'sql' => "varchar(64) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_page']['fields']['abTestVariant'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['abTestVariant'],
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
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][] = 'enableAbTest';
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['enableAbTest'] = 'abTestGroup,abTestVariant';

// Add fields to page palettes (nur für regular und forward pages)
$pageTypes = ['regular', 'forward', 'redirect'];
foreach ($pageTypes as $type) {
    if (isset($GLOBALS['TL_DCA']['tl_page']['palettes'][$type])) {
        $palette = $GLOBALS['TL_DCA']['tl_page']['palettes'][$type];
        
        // Add A/B test fields vor expert section
        if (strpos($palette, '{expert_legend') !== false) {
            $GLOBALS['TL_DCA']['tl_page']['palettes'][$type] = str_replace(
                '{expert_legend',
                '{abtest_legend},enableAbTest;{expert_legend',
                $palette
            );
        } elseif (strpos($palette, '{cache_legend') !== false) {
            $GLOBALS['TL_DCA']['tl_page']['palettes'][$type] = str_replace(
                '{cache_legend',
                '{abtest_legend},enableAbTest;{cache_legend',
                $palette
            );
        } else {
            // Fallback: Am Ende hinzufügen
            $GLOBALS['TL_DCA']['tl_page']['palettes'][$type] .= ';{abtest_legend},enableAbTest';
        }
    }
} 