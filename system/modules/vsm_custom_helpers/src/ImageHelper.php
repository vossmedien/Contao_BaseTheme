<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
use Contao\Image\ResizeConfiguration;


class ImageHelper
{
    public static function generateImageHTML(
        $imageSource,
        $altText = '',
        $headline = '',
        $size = null,
        $class = '',
        $inSlider = false,
        $colorBox = false,
        $lazy = true
    )
    {
        $imageObject = FilesModel::findByUuid($imageSource);
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $globalLanguage = System::getContainer()->getParameter('kernel.default_locale');
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? $globalLanguage;

        if ($imageObject) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = $imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [];
        }

        $relativeImagePath = $imageObject->path;
        $absoluteImagePath = $rootDir . '/' . $relativeImagePath;

        $config = new ResizeConfiguration();
        $originalWidth = 0;
        $originalHeight = 0;

        try {
            $imageDimensions = getimagesize($absoluteImagePath);
            $originalWidth = $imageDimensions[0];
            $originalHeight = $imageDimensions[1];

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

                $processedImage = $imageFactory->create($absoluteImagePath, $config);
                $imageSrc = $processedImage->getPath();
                $imageSrc = str_replace($rootDir, "", $imageSrc);

                $resizeWidth = $width ?? $originalWidth;
            } else {
                $processedImage = $imageFactory->create($absoluteImagePath, $config);
                $imageSrc = $processedImage->getPath();
                $imageSrc = str_replace($rootDir, "", $imageSrc);

                $resizeWidth = $originalWidth;
            }
        } catch (\Exception $e) {
            echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
        }

        $alt = '';
        if (is_array($meta)) {
            $alt = $meta['alt'] ?? $meta['title'] ?? '';
        }
        if (empty($alt) && is_string($altText)) {
            $alt = $altText;
        }
        if (empty($alt) && is_string($headline)) {
            $alt = $headline;
        }

        $title = '';
        if (is_string($headline)) {
            $title = $headline;
        } elseif (is_array($meta)) {
            $title = $meta['title'] ?? $meta['alt'] ?? '';
        }
        if (empty($title) && is_string($altText)) {
            $title = $altText;
        }

        $link = is_array($meta) ? ($meta['link'] ?? '') : '';
        $caption = is_array($meta) ? ($meta['caption'] ?? '') : '';

        if (!empty($colorBox)) {
            $linkStart = '<a title="' . $title . '" data-gall="group_' . htmlspecialchars($colorBox) . '" href="' . htmlspecialchars($relativeImagePath) . '" class="lightbox_' . htmlspecialchars($colorBox) . '">';
            $linkEnd = '</a>';
        } else {
            if (!empty($link)) {
                $linkStart = '<a href="' . htmlspecialchars($link) . '" title="' . htmlspecialchars($title) . '">';
                $linkEnd = '</a>';
            } else {
                $linkStart = '';
                $linkEnd = '';
            }
        }


        $GLOBALS['TL_HEAD'][] = '<link rel="preload" href="' . htmlspecialchars($imageSrc) . '" as="image">';

        // Bildgrößen für das <picture>-Tag generieren
        $srcSetParts = [];
        $breakpoints = [480, 768, 992, 1200, 1600, 1920];

        foreach ($breakpoints as $bp) {
            // Entscheiden, ob die Breakpoints anhand der Originalbildgröße oder der übergebenen $size-Variable verwendet werden
            if ($size && is_array($size) && ($size[0] != "" && $size[1] != "" && $size[2] != "")) {
                if ($bp <= $size[0]) {
                    try {
                        $config->setWidth($bp);
                        $processedImage = $imageFactory->create($absoluteImagePath, $config);
                        $srcSetParts[] = $processedImage->getPath() . ' ' . $bp . 'w';
                    } catch (\Exception $e) {
                        echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
                    }
                }
            } else {
                if ($bp <= $originalWidth) {
                    try {
                        $config->setWidth($bp);
                        $processedImage = $imageFactory->create($absoluteImagePath, $config);
                        $srcSetParts[] = $processedImage->getPath() . ' ' . $bp . 'w';
                    } catch (\Exception $e) {
                        echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
                    }
                }
            }
        }

        $srcSet = implode(', ', array_map(fn($path) => htmlspecialchars(str_replace($rootDir, "", $path)), $srcSetParts));

        // Generierung des Bild-HTML mit <picture>-Tag
        $classAttribute = $class ? ' class="' . htmlspecialchars($class) . '"' : ' class=""';

        $pictureTag = '<picture>';
        if ($srcSet) {
            $pictureTag .= '<source srcset="' . $srcSet . '" sizes="(max-width: 480px) 480px, (max-width: 768px) 768px, (max-width: 992px) 992px, (max-width: 1200px) 1200px, (max-width: 1600px) 1600px, 1920px">';
        }
        $pictureTag .= '<img' . $classAttribute . ' src="' . htmlspecialchars($imageSrc) . '" alt="' . htmlspecialchars($alt) . '"' . ($lazy ? ' loading="lazy"' : '') . '>';
        $pictureTag .= '</picture>';

        // Ausgabe des vollständigen HTML
        $output = $linkStart . $pictureTag . $linkEnd;

        return $output;
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
