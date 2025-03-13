<?php

namespace VSM_HelperFunctions;

/**
 * Legacy-Unterstützung für den alten Namespace
 * Diese Datei dient als Delegation zur neuen Klasse
 */
class ImageHelper
{
    /**
     * Delegiert alle statischen Methodenaufrufe an die neue Klasse
     */
    public static function __callStatic($name, $arguments)
    {
        return \Vsm\VsmHelperTools\Helper\ImageHelper::$name(...$arguments);
    }

    /**
     * Explizit die wichtigsten Methoden definieren, um IDE-Unterstützung zu gewährleisten
     */
    public static function generateImageHTML(
        $imageSource,
        ?string $altText = '',
        ?string $headline = '',
        array|string|null $size = null,
        ?string $class = '',
        bool $inSlider = false,
        $colorBox = false,
        bool $lazy = true,
        ?string $caption = '',
        ?string $imageUrl = ''
    ): string {
        return \Vsm\VsmHelperTools\Helper\ImageHelper::generateImageHTML(
            $imageSource,
            $altText,
            $headline,
            $size,
            $class,
            $inSlider,
            $colorBox,
            $lazy,
            $caption,
            $imageUrl
        );
    }
} 