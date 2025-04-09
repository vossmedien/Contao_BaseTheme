<?php

/*
 * This file is part of Caeli Deploy.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-deploy
 */

use CaeliWind\CaeliDeploy\Controller\DeployController;
use CaeliWind\CaeliDeploy\Model\CaeliDeployModel;

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_caeli_deploy'] = CaeliDeployModel::class;

// Alte Konfiguration deaktivieren
if (isset($GLOBALS['BE_MOD']['caeli']['deploy'])) {
    unset($GLOBALS['BE_MOD']['caeli']['deploy']);
}

// Backend-Module registrieren mit besserem Label
$GLOBALS['BE_MOD']['caeli']['deploy_to_live'] = [
    'callback' => DeployController::class,
    'label' => 'Deploy to LIVE-System',
    'icon'  => 'bundles/caelideploy/icon.svg',
];
