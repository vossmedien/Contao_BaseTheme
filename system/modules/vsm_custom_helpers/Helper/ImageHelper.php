<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;

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
            error_log("File does not exist: $absoluteImagePath");
            //return '';
        }

        $baseImagePath = $absoluteImagePath;
        $baseImagePath = dirname($baseImagePath) . "/" . rawurlencode(basename($baseImagePath));

        // Überprüfen, ob es sich um eine SVG-Datei handelt
        $isSvg = strtolower(pathinfo($baseImagePath, PATHINFO_EXTENSION)) === 'svg';

        if ($isSvg) {
            // Für SVGs verwenden wir die ursprüngliche Datei ohne Verarbeitung
            $imageSrc = str_replace($rootDir, '', $baseImagePath);
            $imageSrc = dirname($imageSrc) . '/' . rawurlencode(basename($imageSrc));

            $alt = !empty($meta['alt']) ? $meta['alt'] : (!empty($altText) ? $altText : (!empty($headline) ? $headline : 'SVG Bild'));

            $style = '';
            if ($size && is_array($size)) {
                $width = isset($size[0]) && $size[0] !== '' ? (int)$size[0] : null;
                $height = isset($size[1]) && $size[1] !== '' ? (int)$size[1] : null;

                if ($width) {
                    $style .= "width: {$width}px; ";
                }
                if ($height) {
                    $style .= "height: {$height}px; ";
                }
            }

            $svgTag = sprintf('<img data-src="%s" alt="%s" class="lazy %s"%s>',
                $imageSrc,
                htmlspecialchars($alt),
                htmlspecialchars($class),
                $style ? ' style="' . htmlspecialchars($style) . '"' : ''
            );

            return '<figure>' . $svgTag . '</figure>';
        }

        // Rest des bestehenden Codes für Nicht-SVG-Bilder
        $originalImageInfo = getimagesize($baseImagePath);
        if ($originalImageInfo === false) {
            error_log("Failed to get image size for: $baseImagePath");
            //return '';
        }
        $originalWidth = (int)$originalImageInfo[0];
        $originalHeight = (int)$originalImageInfo[1];

        // Verdoppeln der übergebenen Größe
        if ($size && is_array($size)) {
            $requestedWidth = isset($size[0]) && $size[0] !== '' ? (int)$size[0] : null;
            $requestedHeight = isset($size[1]) && $size[1] !== '' ? (int)$size[1] : null;
            $doubledWidth = $requestedWidth ? $requestedWidth * 2 : null;
            $doubledHeight = $requestedHeight ? $requestedHeight * 2 : null;
            $mode = $size[2] ?? "proportional";

            // Verwenden der verdoppelten Größe, wenn das Originalbild groß genug ist
            $width = ($doubledWidth && $doubledWidth <= $originalWidth) ? $doubledWidth : $requestedWidth;
            $height = ($doubledHeight && $doubledHeight <= $originalHeight) ? $doubledHeight : $requestedHeight;

            $config = new ResizeConfiguration();

            if ($width !== null) {
                $config->setWidth($width);
            }
            if ($height !== null) {
                $config->setHeight($height);
            }
            if ($mode !== "") {
                $config->setMode($mode);
            }

            try {
                $baseImage = $imageFactory->create($absoluteImagePath, $config);
                $baseImagePath = $baseImage->getPath();
                $baseWidth = $width;
                $baseHeight = $height;
            } catch (\Exception $e) {
                error_log("Error creating base image: " . $e->getMessage());
                //return '';
            }
        } else {
            $baseWidth = $originalWidth;
            $baseHeight = $originalHeight;
        }

        $breakpoints = [
            ['maxWidth' => 576, 'width' => 576],
            ['maxWidth' => 768, 'width' => 768],
            ['maxWidth' => 992, 'width' => 992],
            ['maxWidth' => 1200, 'width' => 1200],
            ['maxWidth' => 1600, 'width' => 1600],
            ['maxWidth' => null, 'width' => $baseWidth]
        ];

        $sources = [];
        $processedSrcsets = [];
        $srcset = [];
        $webpSrcset = [];
        $sizes = [];

        foreach ($breakpoints as $breakpoint) {
            $config = new ResizeConfiguration();
            $width = (int)$breakpoint['width'];
            $mode = $size[2] ?? "proportional";

            if ($width > $baseWidth) {
                continue;
            }

            $config->setWidth($width);
            if ($mode !== "") {
                $config->setMode($mode);
            }

            try {
                // Generiere normales Bild
                $processedImage = $imageFactory->create($baseImagePath, $config);
                $processedImagePath = $processedImage->getPath();

                // Generiere WebP-Version
                $webpOptions = new ResizeOptions();
                $webpOptions->setImagineOptions(['format' => 'webp']);
                $webpImage = $imageFactory->create($baseImagePath, $config, $webpOptions);
                $webpImagePath = $webpImage->getPath();

                try {
                    self::optimizeImage($processedImagePath);
                    self::optimizeImage($webpImagePath);
                } catch (\Exception $e) {
                    error_log("Image optimization failed: " . $e->getMessage());
                }

                $imageSrc = str_replace($rootDir, '', $processedImagePath);
                $imageSrc = dirname($imageSrc) . '/' . rawurlencode(basename($imageSrc));

                $webpSrc = str_replace($rootDir, '', $webpImagePath);
                $webpSrc = dirname($webpSrc) . '/' . rawurlencode(basename($webpSrc));

                $srcset[] = $imageSrc . ' ' . $width . 'w';
                $webpSrcset[] = $webpSrc . ' ' . $width . 'w';

                // Retina Bild (2x und 3x für mobile, nur 2x für andere)
                $retina2xWidth = min($width * 2, $originalWidth);
                $retina3xWidth = min($width * 3, $originalWidth);
                $retinaImageSrc = $imageSrc; // Default to normal image
                $retinaWebpSrc = $webpSrc; // Default to normal WebP image
                $retina3xImageSrc = $imageSrc; // Default to normal image for 3x
                $retina3xWebpSrc = $webpSrc; // Default to normal WebP image for 3x

                if ($retina2xWidth > $width) {
                    $retina2xConfig = clone $config;
                    $retina2xConfig->setWidth($retina2xWidth);
                    $retina2xImage = $imageFactory->create($baseImagePath, $retina2xConfig);
                    $retina2xImagePath = $retina2xImage->getPath();

                    $retina2xWebpImage = $imageFactory->create($baseImagePath, $retina2xConfig, $webpOptions);
                    $retina2xWebpImagePath = $retina2xWebpImage->getPath();

                    try {
                        self::optimizeImage($retina2xImagePath);
                        self::optimizeImage($retina2xWebpImagePath);
                    } catch (\Exception $e) {
                        error_log("Retina 2x image optimization failed: " . $e->getMessage());
                    }

                    $retinaImageSrc = str_replace($rootDir, '', $retina2xImagePath);
                    $retinaImageSrc = dirname($retinaImageSrc) . '/' . rawurlencode(basename($retinaImageSrc));
                    $srcset[] = $retinaImageSrc . ' ' . $retina2xWidth . 'w';

                    $retinaWebpSrc = str_replace($rootDir, '', $retina2xWebpImagePath);
                    $retinaWebpSrc = dirname($retinaWebpSrc) . '/' . rawurlencode(basename($retinaWebpSrc));
                    $webpSrcset[] = $retinaWebpSrc . ' ' . $retina2xWidth . 'w';
                }

                // 3x Version nur für mobile Breakpoints (576px und 768px)
                if ($width <= 768 && $retina3xWidth > $retina2xWidth) {
                    $retina3xConfig = clone $config;
                    $retina3xConfig->setWidth($retina3xWidth);
                    $retina3xImage = $imageFactory->create($baseImagePath, $retina3xConfig);
                    $retina3xImagePath = $retina3xImage->getPath();

                    $retina3xWebpImage = $imageFactory->create($baseImagePath, $retina3xConfig, $webpOptions);
                    $retina3xWebpImagePath = $retina3xWebpImage->getPath();

                    try {
                        self::optimizeImage($retina3xImagePath);
                        self::optimizeImage($retina3xWebpImagePath);
                    } catch (\Exception $e) {
                        error_log("Retina 3x image optimization failed: " . $e->getMessage());
                    }

                    $retina3xImageSrc = str_replace($rootDir, '', $retina3xImagePath);
                    $retina3xImageSrc = dirname($retina3xImageSrc) . '/' . rawurlencode(basename($retina3xImageSrc));
                    $srcset[] = $retina3xImageSrc . ' ' . $retina3xWidth . 'w';

                    $retina3xWebpSrc = str_replace($rootDir, '', $retina3xWebpImagePath);
                    $retina3xWebpSrc = dirname($retina3xWebpSrc) . '/' . rawurlencode(basename($retina3xWebpSrc));
                    $webpSrcset[] = $retina3xWebpSrc . ' ' . $retina3xWidth . 'w';
                }

                if ($breakpoint['maxWidth']) {
                    $sizes[] = '(max-width: ' . $breakpoint['maxWidth'] . 'px) ' . $width . 'px';
                } else {
                    $sizes[] = $width . 'px';
                }

                if ($breakpoint['maxWidth']) {
                    if (!in_array($imageSrc, $processedSrcsets)) {
                        $mediaQuery = "(max-width: {$breakpoint['maxWidth']}px)";
                        if ($width <= 768) {
                            $sources[] = "<source type=\"image/webp\" data-srcset=\"{$webpSrc} 1x, {$retinaWebpSrc} 2x, {$retina3xWebpSrc} 3x\" media=\"{$mediaQuery}\">";
                            $sources[] = "<source data-srcset=\"{$imageSrc} 1x, {$retinaImageSrc} 2x, {$retina3xImageSrc} 3x\" media=\"{$mediaQuery}\">";
                        } else {
                            $sources[] = "<source type=\"image/webp\" data-srcset=\"{$webpSrc} 1x, {$retinaWebpSrc} 2x\" media=\"{$mediaQuery}\">";
                            $sources[] = "<source data-srcset=\"{$imageSrc} 1x, {$retinaImageSrc} 2x\" media=\"{$mediaQuery}\">";
                        }
                        $processedSrcsets[] = $imageSrc;
                    }
                } else {
                    $sources[] = "<source type=\"image/webp\" data-srcset=\"{$webpSrc} 1x, {$retinaWebpSrc} 2x\">";
                    $sources[] = "<source data-srcset=\"{$imageSrc} 1x, {$retinaImageSrc} 2x\">";
                }
            } catch (\Exception $e) {
                error_log("Error processing image for breakpoint {$width}px: " . $e->getMessage());
                continue;
            }
        }

        $lightboxImageSrc = $imageSrc; // Default to normal image for lightbox

        if ($colorBox && ($originalWidth > 1200 || $originalHeight > 1200)) {
            // Generate a larger version for the lightbox only if the original is larger
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
                error_log("Error creating lightbox image: " . $e->getMessage());
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
        $webpSrcsetAttribute = implode(', ', $webpSrcset);
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
            $finalOutput = str_replace("data-srcset", "srcset", $finalOutput);
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
                $result = imagejpeg($image, $imagePath, 95);
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
                // Erhalte Transparenz
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $result = imagepng($image, $imagePath, 6); // Reduzierte Kompression für bessere Qualität
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
