<?php

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

/*
 * Frontend modules
 */
$GLOBALS['FE_MOD']['user']['caeli_pin_login'] = PinLoginController::class;
