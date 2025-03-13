<?php

namespace VSM_HelperFunctions;

/**
 * Legacy-Unterstützung für den alten Namespace
 * Diese Datei dient als Delegation zur neuen Klasse
 */
class GlobalElementConfig
{
    /**
     * Delegiert alle statischen Methodenaufrufe an die neue Klasse
     */
    public static function __callStatic($name, $arguments)
    {
        return \Vsm\VsmHelperTools\Helper\GlobalElementConfig::$name(...$arguments);
    }

    /**
     * Explizit die wichtigsten Methoden definieren, um IDE-Unterstützung zu gewährleisten
     */
    public static function getConfig($key, $default = null)
    {
        return \Vsm\VsmHelperTools\Helper\GlobalElementConfig::getConfig($key, $default);
    }
} 