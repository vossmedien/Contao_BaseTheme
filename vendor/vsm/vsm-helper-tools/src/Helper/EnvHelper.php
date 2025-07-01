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

/**
 * Environment Helper
 * 
 * Stellt Hilfsmethoden zur Verfügung um die aktuelle Contao-Umgebung
 * (Backend/Frontend) zu ermitteln.
 */
class EnvHelper
{
    // Container Cache für Performance
    private static $container = null;

    /**
     * Optimierter Container-Zugriff
     */
    private static function getContainer()
    {
        return self::$container ??= System::getContainer();
    }

    /**
     * Prüft ob der aktuelle Request im Contao Backend läuft
     * 
     * @return bool True wenn Backend, false wenn Frontend
     */
    public static function isBackend(): bool
    {
        try {
            $container = self::getContainer();
            $requestStack = $container->get('request_stack');
            $scopeMatcher = $container->get('contao.routing.scope_matcher');
            
            $currentRequest = $requestStack->getCurrentRequest();
            
            return $currentRequest && $scopeMatcher->isBackendRequest($currentRequest);
        } catch (\Exception $e) {
            // Bei Fehlern konservativ Backend annehmen
            self::logError('Fehler bei Backend-Erkennung: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Prüft ob der aktuelle Request im Contao Frontend läuft
     * 
     * @return bool True wenn Frontend, false wenn Backend
     */
    public static function isFrontend(): bool
    {
        return !self::isBackend();
    }

    /**
     * Schreibt eine Fehler-Nachricht ins Log
     */
    private static function logError(string $message): void
    {
        error_log('[EnvHelper ERROR] ' . $message);
    }
}