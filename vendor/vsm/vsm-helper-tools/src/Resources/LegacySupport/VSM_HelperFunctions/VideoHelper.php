<?php

namespace VSM_HelperFunctions;

/**
 * Legacy-Unterstützung für den alten Namespace
 * Diese Datei dient als Delegation zur neuen Klasse
 */
class VideoHelper
{
    /**
     * Delegiert alle statischen Methodenaufrufe an die neue Klasse
     */
    public static function __callStatic($name, $arguments)
    {
        return \Vsm\VsmHelperTools\Helper\VideoHelper::$name(...$arguments);
    }

    /**
     * Explizit die wichtigsten Methoden definieren, um IDE-Unterstützung zu gewährleisten
     */
    public static function generateVideoEmbed($videoUrl, $width = null, $height = null, $autoplay = false, $responsive = true)
    {
        return \Vsm\VsmHelperTools\Helper\VideoHelper::generateVideoEmbed($videoUrl, $width, $height, $autoplay, $responsive);
    }
} 