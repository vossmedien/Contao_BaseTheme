<?php

namespace VSM_HelperFunctions;

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