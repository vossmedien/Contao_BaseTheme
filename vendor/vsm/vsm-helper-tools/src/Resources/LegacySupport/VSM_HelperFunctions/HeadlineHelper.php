<?php

namespace VSM_HelperFunctions;

/**
 * Legacy-Unterstützung für den alten Namespace
 * Diese Datei dient als Delegation zur neuen Klasse
 */
class HeadlineHelper
{
    /**
     * Delegiert alle statischen Methodenaufrufe an die neue Klasse
     */
    public static function __callStatic($name, $arguments)
    {
        return \Vsm\VsmHelperTools\Helper\HeadlineHelper::$name(...$arguments);
    }

    /**
     * Explizit die wichtigsten Methoden definieren, um IDE-Unterstützung zu gewährleisten
     */
    public static function getHeadline($headline, $as = 'h2', $class = '', $id = null)
    {
        return \Vsm\VsmHelperTools\Helper\HeadlineHelper::getHeadline($headline, $as, $class, $id);
    }
} 