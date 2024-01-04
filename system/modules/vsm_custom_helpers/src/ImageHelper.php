<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
use Contao\Image;

class ImageHelper
{
    public static function generateImageHTML($imageSource, $altText = '', $headline = '', $size = null, $class = '', $inSlider = false, $lazy = true)
    {
        $imageObject = FilesModel::findByUuid($imageSource);
        $isPath = false;
        $globalLanguage = System::getContainer()->getParameter('kernel.default_locale');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? $globalLanguage;

        if (!$imageObject) {
            $isPath = true;
        } else {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = $imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [];
        }


        // Alt und Titel von Metadaten oder alternativ Text verwenden; Headline als Fallback für den Titel
        $alt = $meta['alt'] ?: ($meta['title'] ?: ($headline ?: ''));
        $title = $meta['title'] ?: ($headline ?: $altText);
        $link = $meta['link'] ?: '';
        $caption = $meta['caption'] ?: '';

        // Verwendet die Standardgröße [null, null, null], wenn $size null ist
        $sizeParams = $size ?: [null, null, null];

        // Pfad zum Bild generieren
        if ($isPath) {
            $imageSrc = $imageSource;
        } else {
            $imageSrc = Image::get($imageObject->path, $sizeParams[0], $sizeParams[1], $sizeParams[2]);
        }

        // Erstellung des HTML-Links, falls vorhanden
        $linkStart = $link ? '<a href="' . htmlspecialchars($link) . '" title="' . htmlspecialchars($title) . '">' : '';
        $linkEnd = $link ? '</a>' : '';

        // Hinzufügen der Klasse, falls vorhanden
        // Erstellung des Bild-HTML-Codes
        if ($lazy) {
            if ($inSlider) {
                $classAttribute = $class ? ' class=" ' . htmlspecialchars($class) . '"' : ' class=""';
                $imageHTML = $linkStart . '<div class="swiper-lazy-preloader"></div><img' . $classAttribute . ' loading="lazy" src="' . $imageSrc . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            } else {
                $classAttribute = $class ? ' class="lazy ' . htmlspecialchars($class) . '"' : ' class="lazy"';
                $imageHTML = $linkStart . '<img' . $classAttribute . ' loading="lazy" data-src="' . $imageSrc . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            }
        } else {
            if ($inSlider) {
                $classAttribute = $class ? ' class=" ' . htmlspecialchars($class) . '"' : ' class=""';
                $imageHTML = $linkStart . '<img' . $classAttribute . '  src="' . $imageSrc . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            } else {
                $classAttribute = $class ? ' class=" ' . htmlspecialchars($class) . '"' : ' ';
                $imageHTML = $linkStart . '<img' . $classAttribute . ' src="' . $imageSrc . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            }

        }


        // Hinzufügen der Bildunterschrift, falls vorhanden
        if ($caption) {
            $imageHTML .= '<figcaption class="mt-1 text-muted fs-6">' . htmlspecialchars($caption) . '</figcaption>';
        }

        return $imageHTML;
    }
}
