<?php

declare(strict_types=1);

/*
 * This file is part of VSM Deploy.
 *
 * (c) VSM 2025
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 */

// Backend-Module
$GLOBALS['BE_MOD']['vsm']['vsm_deploy'] = [
    'callback' => 'VSM\VsmDeploy\Controller\DeployController',
    'icon' => 'bundles/vsmdeploy/icon.svg',
    'stylesheet' => 'bundles/vsmdeploy/styles.css',
    'javascript' => 'bundles/vsmdeploy/script.js'
];