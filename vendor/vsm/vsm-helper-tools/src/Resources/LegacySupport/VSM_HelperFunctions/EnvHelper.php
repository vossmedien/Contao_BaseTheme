<?php

namespace VSM_HelperFunctions;

/**
 * Legacy-Unterstützung für den alten Namespace
 * Diese Datei dient als Delegation zur neuen Klasse
 */
class EnvHelper
{
    /**
     * Delegiert alle statischen Methodenaufrufe an die neue Klasse
     */
    public static function __callStatic($name, $arguments)
    {
        return \Vsm\VsmHelperTools\Helper\EnvHelper::$name(...$arguments);
    }

    /**
     * Explizit die wichtigsten Methoden definieren, um IDE-Unterstützung zu gewährleisten
     */
    public static function getEnv($key, $default = null)
    {
        return \Vsm\VsmHelperTools\Helper\EnvHelper::getEnv($key, $default);
    }
} 