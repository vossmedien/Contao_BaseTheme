<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Add fields to tl_article
$GLOBALS['TL_DCA']['tl_article']['fields']['enableAbTest'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_article']['enableAbTest'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_article']['fields']['abTestVariant'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_article']['abTestVariant'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['Vsm\VsmAbTest\EventListener\DataContainerListener', 'getAbTestVariantOptions'],
    'eval' => [
        'includeBlankOption' => true,
        'tl_class' => 'w50',
        'mandatory' => true
    ],
    'sql' => "varchar(32) NOT NULL default ''"
];

// Add selector for subpalette
$GLOBALS['TL_DCA']['tl_article']['palettes']['__selector__'][] = 'enableAbTest';
$GLOBALS['TL_DCA']['tl_article']['subpalettes']['enableAbTest'] = 'abTestVariant';

// Add fields to article palette mit korrekter Legende
if (strpos($GLOBALS['TL_DCA']['tl_article']['palettes']['default'], '{expert_legend') !== false) {
    $GLOBALS['TL_DCA']['tl_article']['palettes']['default'] = str_replace(
        '{expert_legend',
        '{abtest_legend},enableAbTest;{expert_legend',
        $GLOBALS['TL_DCA']['tl_article']['palettes']['default']
    );
} else {
    $GLOBALS['TL_DCA']['tl_article']['palettes']['default'] .= ';{abtest_legend},enableAbTest';
} 