<?php

namespace VSM_HelperFunctions;

/**
 * Legacy-Unterstützung für den alten Namespace
 * Diese Datei dient als Delegation zur neuen Klasse
 */
class BasicHelper
{
    /**
     * Delegiert alle statischen Methodenaufrufe an die neue Klasse
     */
    public static function __callStatic($name, $arguments)
    {
        return \Vsm\VsmHelperTools\Helper\BasicHelper::$name(...$arguments);
    }

    /**
     * Explizit die wichtigsten Methoden definieren, um IDE-Unterstützung zu gewährleisten
     */
    public static function cleanColor($color)
    {
        // Null-Check
        if ($color === null) {
            return '';
        }
        
        return \Vsm\VsmHelperTools\Helper\BasicHelper::cleanColor($color);
    }
    
    public static function getFileInfo($uuid)
    {
        if ($uuid === null) {
            return ['filename' => '', 'ext' => ''];
        }
        
        return \Vsm\VsmHelperTools\Helper\BasicHelper::getFileInfo($uuid);
    }
} 