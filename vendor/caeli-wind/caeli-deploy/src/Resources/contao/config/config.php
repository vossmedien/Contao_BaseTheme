<?php

// Backend-Module registrieren mit besserem Label
$GLOBALS['BE_MOD']['caeli']['deploy_to_live'] = [
    'callback' => 'CaeliWind\CaeliDeploy\Controller\DeployController::index',
    'label' => 'Deploy to LIVE-System',
    'icon'  => 'bundles/caelideploy/icon.svg',
];

// Alte Konfiguration deaktivieren
if (isset($GLOBALS['BE_MOD']['caeli']['deploy'])) {
    unset($GLOBALS['BE_MOD']['caeli']['deploy']);
} 