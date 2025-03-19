<?php

declare(strict_types=1);

/*
 * This file is part of Caeli PIN-Login.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-pin-login
 */

use CaeliWind\CaeliPinLogin\Controller\FrontendModule\PinLoginController;

/**
 * Frontend modules
 */
// PIN-Login Modul
$GLOBALS['TL_DCA']['tl_module']['palettes'][PinLoginController::TYPE] = '{title_legend},name,headline,type;{config_legend},requireEmail,extraDataField;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

// Felder hinzufügen
$GLOBALS['TL_DCA']['tl_module']['fields']['requireEmail'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class' => 'w50 clr'],
    'sql'                     => "char(1) NOT NULL default ''",
    'xlabel'                  => ['tl_module', 'requireEmail_info']
];

$GLOBALS['TL_DCA']['tl_module']['fields']['extraDataField'] = [
    'exclude'                 => true,
    'inputType'               => 'checkbox',
    'eval'                    => ['tl_class' => 'w50'],
    'sql'                     => "char(1) NOT NULL default ''",
    'xlabel'                  => ['tl_module', 'extraDataField_info']
];
