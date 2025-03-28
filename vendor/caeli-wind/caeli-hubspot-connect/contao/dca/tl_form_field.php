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

// Paletten erweitern
foreach (['text', 'textarea', 'select', 'checkbox', 'radio', 'password', 'upload', 'hidden', 'captcha'] as $fieldType) {
    $GLOBALS['TL_DCA']['tl_form_field']['palettes'][$fieldType] = str_replace(
        '{expert_legend',
        '{hubspot_legend},hubspotFieldName;{expert_legend',
        $GLOBALS['TL_DCA']['tl_form_field']['palettes'][$fieldType]
    );
}

// Hubspot Feld-Mapping
$GLOBALS['TL_DCA']['tl_form_field']['fields']['hubspotFieldName'] = [
    'exclude'   => true,
    'inputType' => 'text', 
    'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql'       => "varchar(255) NOT NULL default ''"
]; 