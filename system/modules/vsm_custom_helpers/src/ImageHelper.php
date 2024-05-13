<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
use Contao\Image\ResizeConfiguration;


class ImageHelper
{
    public static function generateImageHTML($imageSource, $altText = '', $headline = '', $size = null, $class = '', $inSlider = false, $colorBox = false, $lazy = true)
    {
        $imageObject = FilesModel::findByUuid($imageSource);
        $originalSrc = $imageObject->path;
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $globalLanguage = System::getContainer()->getParameter('kernel.default_locale');
        $imageFactory = System::getContainer()->get('contao.image.factory');

        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? $globalLanguage;

        if ($imageObject) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = $imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [];
        }


        // Bildgrößenkonfiguration definieren
        $config = new ResizeConfiguration();


        if ($size && is_array($size) && ($size[0] != "" && $size[1] != "" && $size[2] != "")) {
            $width = isset($size[0]) ? (int)$size[0] : null;
            $height = isset($size[1]) ? (int)$size[1] : null;
            $mode = $size[2] ?? null;

            if ($width !== null) {
                $config->setWidth($width);
            }
            if ($height !== null) {
                $config->setHeight($height);
            }
            if ($mode !== null) {
                $config->setMode($mode);
            }
        } else {
            $size = $size ?: [null, null, null];
        }

        $relativeImagePath = $imageObject->path;
        $absoluteImagePath = $rootDir . '/' . $relativeImagePath;


        try {
            $processedImage = $imageFactory->create($absoluteImagePath, $config);
            $imageSrc = $processedImage->getPath();
        } catch (\Exception $e) {
            echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
        }

        $imageSrc = str_replace($rootDir, "", $imageSrc);
        $alt = $meta['alt'] ?? $meta['title'] ?? $altText ?? $headline ?? '';
        $title = $headline ?: $meta['title'] ?: $meta['alt'] ?: $altText ?: '';
        $link = $meta['link'] ?: '';
        $caption = $meta['caption'] ?: '';


        // Hinzufügen der Klasse, falls vorhanden
        // Erstellung des Bild-HTML-Codes
        if (!empty($colorBox)) {
            $linkStart = '<a title="' . $title . '" data-gall="group_' . htmlspecialchars($colorBox) . '" href="' . htmlspecialchars($originalSrc) . '" class="lightbox_' . htmlspecialchars($colorBox) . '">';
            $linkEnd = '</a>';
        } else {
            if (!empty($link)) {
                // Erstellung des HTML-Links, falls vorhanden
                $linkStart = $link ? '<a href="' . htmlspecialchars($link) . '" title="' . htmlspecialchars($title) . '">' : '';
                $linkEnd = $link ? '</a>' : '';
            } else {
                $linkStart = '';
                $linkEnd = '';
            }
        }

        if ($lazy) {
            if ($inSlider) {
                $classAttribute = $class ? ' class="' . htmlspecialchars($class) . '"' : ' class=""';
                $imageHTML = $linkStart . '<div class="swiper-lazy-preloader"></div><img' . $classAttribute . ' loading="lazy" src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            } else {
                $classAttribute = $class ? ' class="lazy ' . htmlspecialchars($class) . '"' : ' class="lazy"';
                $imageHTML = $linkStart . '<img' . $classAttribute . ' loading="lazy" data-src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            }
        } else {
            if ($inSlider) {
                $classAttribute = $class ? ' class="' . htmlspecialchars($class) . '"' : ' class=""';
                $imageHTML = $linkStart . '<img' . $classAttribute . ' src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            } else {
                $classAttribute = $class ? ' class="' . htmlspecialchars($class) . '"' : ' ';
                $imageHTML = $linkStart . '<img' . $classAttribute . ' src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($alt) . '" title="' . htmlspecialchars($title) . '">' . $linkEnd;
            }
        }

        // Hinzufügen der Bildunterschrift, falls vorhanden
        if ($caption) {
            $imageHTML .= '<figcaption class="">' . htmlspecialchars($caption) . '</figcaption>';
        }

        return $imageHTML;
    }


    public static function generateImageURL($imageSource, $size = null)
    {
        $imageObject = FilesModel::findByUuid($imageSource);
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $imageFactory = System::getContainer()->get('contao.image.factory');

        // Bildgrößenkonfiguration definieren
        $config = new ResizeConfiguration();

        if ($size && is_array($size) && ($size[0] != "" && $size[1] != "" && $size[2] != "")) {
            $width = isset($size[0]) ? (int)$size[0] : null;
            $height = isset($size[1]) ? (int)$size[1] : null;
            $mode = $size[2] ?? null;

            if ($width !== null) {
                $config->setWidth($width);
            }
            if ($height !== null) {
                $config->setHeight($height);
            }
            if ($mode !== null) {
                $config->setMode($mode);
            }
        }

        $relativeImagePath = $imageObject->path;
        $absoluteImagePath = $rootDir . '/' . $relativeImagePath;

        try {
            $processedImage = $imageFactory->create($absoluteImagePath, $config);
            $imageSrc = $processedImage->getPath();
        } catch (\Exception $e) {
            echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
        }

        $imageSrc = str_replace($rootDir, "", $imageSrc);

        return $imageSrc;
    }

}
