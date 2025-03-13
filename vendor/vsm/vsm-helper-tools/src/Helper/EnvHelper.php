<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */
namespace Vsm\VsmHelperTools\Helper;

use Symfony\Component\HttpFoundation\RequestStack;
use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\System;

class EnvHelper
{
    public static function isBackend()
    {
        $requestStack = System::getContainer()->get('request_stack');
        $scopeMatcher = System::getContainer()->get('contao.routing.scope_matcher');

        return $scopeMatcher->isBackendRequest($requestStack->getCurrentRequest());
    }

    public static function isFrontend()
    {
        return !self::isBackend();
    }
}