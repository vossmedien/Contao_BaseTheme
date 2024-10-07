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
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? System::getContainer()->getParameter('kernel.default_locale');

        if ($imageObject = FilesModel::findByUuid($imageSource)) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = $imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [];
            $relativeImagePath = $imageObject->path;
        } else {
            $relativeImagePath = $imageSource;
            $meta = [];
        }

        $absoluteImagePath = $rootDir . '/' . urldecode($relativeImagePath);

        if (!file_exists($absoluteImagePath)) {
            echo "File does not exist: $absoluteImagePath\n";
            return '';
        }

        $baseImagePath = $absoluteImagePath;
        $baseImagePath = dirname($baseImagePath) . "/" . rawurlencode(basename($baseImagePath));

        if ($size && is_array($size)) {
            $width = isset($size[0]) ? (int)$size[0] : null;
            $height = isset($size[1]) ? (int)$size[1] : null;
            $mode = $size[2] ?? "proportional";

            $config = new ResizeConfiguration();

            if ($width !== "") {
                $config->setWidth($width);
            }
            if ($height !== "") {
                $config->setHeight($height);
            }

            if ($mode !== "") {
                $config->setMode($mode);
            }

            try {
                $baseImage = $imageFactory->create($absoluteImagePath, $config);
                $baseImagePath = $baseImage->getPath();
            } catch (\Exception $e) {
                return '';
            }
        }

        $maxWidth = $size[0] ?? null;
        $breakpoints = [
            //['maxWidth' => 576, 'width' => 576],
            ['maxWidth' => 768, 'width' => 768],
            ['maxWidth' => 992, 'width' => 992],
            ['maxWidth' => 1200, 'width' => 1200],
            ['maxWidth' => 1600, 'width' => 1600],
            ['maxWidth' => null, 'width' => $maxWidth ?: 1920]
        ];

        $sources = [];
        $processedSrcsets = [];
        $srcset = [];
        $sizes = [];

        foreach ($breakpoints as $breakpoint) {
            $config = new ResizeConfiguration();
            $width = $breakpoint['width'];
            $mode = $size[2] ?? "proportional";

            if ($maxWidth && $width > $maxWidth) {
                continue;
            }

            if ($width !== "" && $width != NULL) {
                $config->setWidth($width);
            }

            if ($mode !== "") {
                $config->setMode($mode);
            }

            try {
                $processedImage = $imageFactory->create($baseImagePath, $config);
                $processedImagePath = $processedImage->getPath();

                // Bildoptimierung nach der Generierung
                self::optimizeImage($processedImagePath);

                $currentDomain = $_SERVER['HTTP_HOST'];
                $imageUrl = 'https://' . $currentDomain . str_replace($rootDir, '', $processedImagePath);

                if (!file_exists($processedImagePath)) {
                    $context = stream_context_create(['http' => ['timeout' => 0]]);
                    @file_get_contents($imageUrl, false, $context);
                }

                $imageSrc = str_replace($rootDir, '', $processedImagePath);
                $imageSrc = dirname($imageSrc) . '/' . rawurlencode(basename($imageSrc));

                // Adaptive Bildgrößen
                $srcset[] = $imageSrc . ' ' . $breakpoint['width'] . 'w';
                if ($breakpoint['maxWidth']) {
                    $sizes[] = '(max-width: ' . $breakpoint['maxWidth'] . 'px) ' . $breakpoint['width'] . 'px';
                } else {
                    $sizes[] = $breakpoint['width'] . 'px';
                }

                if ($breakpoint['maxWidth']) {
                    if (!in_array($imageSrc, $processedSrcsets)) {
                        $mediaQuery = "(max-width: {$breakpoint['maxWidth']}px)";
                        $sources[] = "<source data-srcset=\"{$imageSrc}\" media=\"{$mediaQuery}\">";
                        $processedSrcsets[] = $imageSrc;
                    }
                } else {
                    $sources[] = "<source data-srcset=\"{$imageSrc}\">";
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        $lightboxImageSrc = $imageSrc; // Default to normal image for lightbox

        if ($colorBox && ($size[0] < 1200 || $size[1] < 1200)) {
            // Generate a larger version for the lightbox
            $lightboxConfig = new ResizeConfiguration();
            $lightboxConfig->setWidth(1200);
            $lightboxConfig->setHeight(1200);
            $lightboxConfig->setMode('box'); // Keeps aspect ratio and fills the box

            try {
                $lightboxImage = $imageFactory->create($absoluteImagePath, $lightboxConfig);
                $lightboxImagePath = $lightboxImage->getPath();
                $lightboxImageSrc = str_replace($rootDir, '', $lightboxImagePath);
                $lightboxImageSrc = dirname($lightboxImageSrc) . '/' . rawurlencode(basename($lightboxImageSrc));
            } catch (\Exception $e) {
                // Error handling if the image can't be created
                // We keep the original image for the lightbox in this case
            }
        }

        if ($inSlider) {
            $classAttribute = $class ? ' class="' . htmlspecialchars($class) . ' "' : '';
        } else {
            $classAttribute = $class ? ' class="lazy ' . htmlspecialchars($class) . ' "' : 'class="lazy"';
        }
        $lazyAttribute = $lazy ? ' loading="lazy"' : '';

        // Verbesserte Alt-Text-Generierung
        $alt = !empty($meta['alt']) ? $meta['alt'] :
            (!empty($meta['title']) ? $meta['title'] :
                (!empty($altText) ? $altText :
                    (!empty($headline) ? $headline :
                        'Bild ' . basename($imageSrc)))); // Fallback mit Dateinamen

        $title = !empty($meta['title']) ? $meta['title'] : (!empty($meta['caption']) ? $meta['caption'] : (!empty($headline) ? $headline : (!empty($meta['alt']) ? $meta['alt'] : (!empty($altText) ? $altText : ''))));
        $link = !empty($meta['link']) ? $meta['link'] : '';
        $caption = !empty($meta['caption']) ? $meta['caption'] : '';

        $linkStart = $linkEnd = '';
        if ($colorBox) {
            $linkStart = sprintf('<a title="%s" data-gall="group_%s" href="%s" class="lightbox_%s">',
                htmlspecialchars($title), htmlspecialchars($colorBox), $lightboxImageSrc, htmlspecialchars($colorBox));
            $linkEnd = '</a>';
        } elseif ($link) {
            $linkStart = sprintf('<a href="%s" title="%s">', htmlspecialchars($link), htmlspecialchars($title));
            $linkEnd = '</a>';
        }

        $srcsetAttribute = implode(', ', $srcset);
        $sizesAttribute = implode(', ', $sizes) . ', 100vw';

        $imgTag = '<picture>';
        $imgTag .= implode("\n", $sources);
        $imgTag .= '<img ' . $classAttribute . ' data-src="' . $imageSrc . '" data-srcset="' . $srcsetAttribute . '" sizes="' . $sizesAttribute . '" alt="' . htmlspecialchars($alt) . '"' . $lazyAttribute . '>';
        $imgTag .= '</picture>';

        $finalOutput = '<figure>' . $imgTag;

        if ($inSlider) {
            $finalOutput .= '<div class="swiper-lazy-preloader"></div>';
            if ($caption) {
                $finalOutput .= '<div class="slider-caption">' . htmlspecialchars($caption) . '</div>';
            }

            $finalOutput = str_replace("data-src", "src", $finalOutput);
            $finalOutput = str_replace('loading="lazy"', '', $finalOutput);
        } else {
            if ($caption) {
                $finalOutput .= '<figcaption>' . htmlspecialchars($caption) . '</figcaption>';
            }
        }

        $finalOutput .= '</figure>';

        if ($linkStart || $linkEnd) {
            $finalOutput = $linkStart . $finalOutput . $linkEnd;
        }

        return $finalOutput;
    }

// Neue Funktion für die Bildoptimierung
    private static function optimizeImage($imagePath)
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        if ($extension === 'jpg' || $extension === 'jpeg') {
            if (function_exists('imagecreatefromjpeg')) {
                $image = @imagecreatefromjpeg($imagePath);
                if ($image === false) {
                    error_log("Failed to create image from JPEG: $imagePath");
                    return;
                }
                $result = imagejpeg($image, $imagePath, 85);
                if ($result === false) {
                    error_log("Failed to save optimized JPEG: $imagePath");
                }
                imagedestroy($image);
            } else {
                error_log("imagecreatefromjpeg function not available");
            }
        } elseif ($extension === 'png') {
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($imagePath);
                if ($image === false) {
                    error_log("Failed to create image from PNG: $imagePath");
                    return;
                }
                $result = imagepng($image, $imagePath, 9);
                if ($result === false) {
                    error_log("Failed to save optimized PNG: $imagePath");
                }
                imagedestroy($image);
            } else {
                error_log("imagecreatefrompng function not available");
            }
        } else {
            error_log("Unsupported image format for optimization: $extension");
        }
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
            $mode = $size[2] ?? "proportional";

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
