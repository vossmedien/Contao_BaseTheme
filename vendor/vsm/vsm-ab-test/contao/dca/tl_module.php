<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

// Add fields to tl_module
$GLOBALS['TL_DCA']['tl_module']['fields']['enableAbTest'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['enableAbTest'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_module']['fields']['abTestVariant'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['abTestVariant'],
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
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'enableAbTest';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['enableAbTest'] = 'abTestVariant';

// Add fields to ALL module palettes
foreach ($GLOBALS['TL_DCA']['tl_module']['palettes'] as $key => $palette) {
    // Skip special palettes
    if ($key === '__selector__' || empty($palette) || !is_string($palette)) {
        continue;
    }
    
    // Add A/B test fields vor template oder expert section
    if (strpos($palette, '{expert_legend') !== false) {
        $GLOBALS['TL_DCA']['tl_module']['palettes'][$key] = str_replace(
            '{expert_legend',
            '{abtest_legend},enableAbTest;{expert_legend',
            $palette
        );
    } elseif (strpos($palette, '{template_legend') !== false) {
        $GLOBALS['TL_DCA']['tl_module']['palettes'][$key] = str_replace(
            '{template_legend',
            '{abtest_legend},enableAbTest;{template_legend',
            $palette
        );
    } elseif (strpos($palette, '{invisible_legend') !== false) {
        $GLOBALS['TL_DCA']['tl_module']['palettes'][$key] = str_replace(
            '{invisible_legend',
            '{abtest_legend},enableAbTest;{invisible_legend',
            $palette
        );
    } else {
        // Fallback: Am Ende hinzufügen
        $GLOBALS['TL_DCA']['tl_module']['palettes'][$key] .= ';{abtest_legend},enableAbTest';
    }
} 