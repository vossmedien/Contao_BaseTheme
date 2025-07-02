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

namespace Vsm\VsmHelperTools\Helper\Traits;

use Contao\System;

/**
 * Helper Trait
 * 
 * Stellt gemeinsame Basis-Funktionalität für alle Helper zur Verfügung
 * wie Container-Zugriff, Logging und Error-Handling.
 */
trait HelperTrait
{
    // Container Cache für Performance
    private static $container = null;
    
    /**
     * Optimierter Container-Zugriff
     */
    protected static function getContainer()
    {
        return self::$container ??= System::getContainer();
    }
    
    /**
     * Schreibt eine Fehler-Nachricht ins Log
     */
    protected static function logError(string $message, array $context = []): void
    {
        $helperName = basename(str_replace('\\', '/', static::class));
        
        try {
            $container = self::getContainer();
            if ($container->has('monolog.logger.contao')) {
                $container->get('monolog.logger.contao')->error(
                    "[{$helperName}] {$message}", 
                    $context
                );
            }
        } catch (\Exception $e) {
            // Fallback auf error_log
        }
        
        error_log("[{$helperName} ERROR] {$message}");
    }
    
    /**
     * Schreibt eine Info-Nachricht ins Log
     */
    protected static function logInfo(string $message, array $context = []): void
    {
        $helperName = basename(str_replace('\\', '/', static::class));
        
        try {
            $container = self::getContainer();
            if ($container->has('monolog.logger.contao')) {
                $container->get('monolog.logger.contao')->info(
                    "[{$helperName}] {$message}", 
                    $context
                );
            }
        } catch (\Exception $e) {
            // Info-Logs nur im Monolog, nicht im error_log
        }
    }
    
    /**
     * Schreibt eine Warnung ins Log
     */
    protected static function logWarning(string $message, array $context = []): void
    {
        $helperName = basename(str_replace('\\', '/', static::class));
        
        try {
            $container = self::getContainer();
            if ($container->has('monolog.logger.contao')) {
                $container->get('monolog.logger.contao')->warning(
                    "[{$helperName}] {$message}", 
                    $context
                );
            }
        } catch (\Exception $e) {
            // Fallback auf error_log
        }
        
        error_log("[{$helperName} WARNING] {$message}");
    }
    
    /**
     * Bereinigt Eingabe-Strings
     */
    protected static function cleanInput(?string $input): string
    {
        return $input !== null ? trim((string)$input) : '';
    }
    
    /**
     * Prüft ob wir im Contao Backend sind
     */
    protected static function isBackend(): bool
    {
        try {
            $container = self::getContainer();
            $requestStack = $container->get('request_stack');
            $scopeMatcher = $container->get('contao.routing.scope_matcher');
            
            $currentRequest = $requestStack->getCurrentRequest();
            
            return $currentRequest && $scopeMatcher->isBackendRequest($currentRequest);
        } catch (\Exception $e) {
            return false;
        }
    }
} 