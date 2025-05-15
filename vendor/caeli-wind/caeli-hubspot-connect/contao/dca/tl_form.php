<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Hubspot Connect.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-hubspot-connect
 */

// Palette erweitern
$GLOBALS['TL_DCA']['tl_form']['palettes']['default'] = str_replace(
    '{config_legend',
    '{hubspot_legend},enableHubspot,hubspotPortalId,hubspotFormId;{config_legend',
    $GLOBALS['TL_DCA']['tl_form']['palettes']['default']
);

// Neue Felder definieren
$GLOBALS['TL_DCA']['tl_form']['fields']['enableHubspot'] = [
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['hubspotPortalId'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'doNotCopy' => true],
    'sql'       => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form']['fields']['hubspotFormId'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50', 'doNotCopy' => true],
    'sql'       => "varchar(255) NOT NULL default ''",
];
