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
        $lazy = true,
        $caption = '',
        $imageUrl = ''
    )
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? System::getContainer()->getParameter('kernel.default_locale');
        $originalWidth = $originalHeight = 0;

        if ($imageObject = FilesModel::findByUuid($imageSource)) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = $imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [];
            $relativeImagePath = $imageObject->path;
        } else {
            $relativeImagePath = $imageSource;
            $meta = [];
        }


        $absoluteImagePath = $rootDir . '/' . urldecode($relativeImagePath);
        $baseImagePath = dirname($absoluteImagePath) . "/" . rawurlencode(basename($absoluteImagePath));

        // SVG Handling
        if (strtolower(pathinfo($baseImagePath, PATHINFO_EXTENSION)) === 'svg') {
            $imageSrc = str_replace($rootDir, '', $baseImagePath);
            $imageSrc = dirname($imageSrc) . '/' . rawurlencode(basename($imageSrc));
            $alt = $altText ?: (!empty($meta['alt']) ? $meta['alt'] : (!empty($headline) ? $headline : 'SVG Bild'));
            $style = '';
            if ($size && is_array($size)) {
                $width = isset($size[0]) && $size[0] !== '' ? (int)$size[0] : null;
                $height = isset($size[1]) && $size[1] !== '' ? (int)$size[1] : null;
                if ($width) $style .= "width: {$width}px; ";
                if ($height) $style .= "height: {$height}px; ";
            }
            $svgTag = sprintf('<img data-src="%s" alt="%s" class="lazy %s"%s>',
                $imageSrc,
                htmlspecialchars($alt),
                htmlspecialchars($class),
                $style ? ' style="' . htmlspecialchars($style) . '"' : ''
            );
            return '<figure>' . $svgTag . '</figure>';
        }

        // Get original image dimensions
        if (file_exists($baseImagePath)) {
            $originalImageInfo = @getimagesize($baseImagePath);
            if ($originalImageInfo && is_array($originalImageInfo)) {
                $originalWidth = (int)$originalImageInfo[0];
                $originalHeight = (int)$originalImageInfo[1];
            }
        }



        if($originalImageInfo) {

            // Image size configuration
            $config = new ResizeConfiguration();
            if ($size && is_array($size)) {
                $requestedWidth = isset($size[0]) && $size[0] !== '' ? (int)$size[0] : null;
                $requestedHeight = isset($size[1]) && $size[1] !== '' ? (int)$size[1] : null;
                $mode = $size[2] ?? "proportional";

                // Prüfen, ob 2x-Version möglich ist
                $canCreate2x = ($requestedWidth * 2 <= $originalWidth) && ($requestedHeight * 2 <= $originalHeight);

                if ($canCreate2x) {
                    $width = $requestedWidth * 2;
                    $height = $requestedHeight * 2;
                } else {
                    $width = $requestedWidth;
                    $height = $requestedHeight;
                }

                if ($width !== null) $config->setWidth($width);
                if ($height !== null) $config->setHeight($height);
                if ($mode !== "") $config->setMode($mode);
            }

            try {
                $baseImage = $imageFactory->create($absoluteImagePath, $config);
                $baseImagePath = $baseImage->getPath();
                $baseWidth = $width ?? $originalWidth;
                $baseHeight = $height ?? $originalHeight;
            } catch (\Exception $e) {
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
                        //error_log("Image optimization failed: " . $e->getMessage());
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
                            //error_log("Retina 2x image optimization failed: " . $e->getMessage());
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
                            //error_log("Retina 3x image optimization failed: " . $e->getMessage());
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
                    //error_log("Error processing image for breakpoint {$width}px: " . $e->getMessage());
                    continue;
                }
            }

            $lightboxImageSrc = $imageSrc;
            if ($colorBox && ($originalWidth > 1200 || $originalHeight > 1200)) {
                $lightboxConfig = new ResizeConfiguration();
                $lightboxConfig->setWidth(1200)->setHeight(1200)->setMode('box');
                try {
                    $lightboxImage = $imageFactory->create($absoluteImagePath, $lightboxConfig);
                    $lightboxImageSrc = self::getRelativeImagePath($rootDir, $lightboxImage->getPath());
                } catch (\Exception $e) {
                    // Fallback to original image
                }
            }

            $classAttribute = $inSlider ? ($class ? ' class="' . htmlspecialchars($class) . '"' : '') :
                ($class ? ' class="lazy ' . htmlspecialchars($class) . '"' : ' class="lazy"');
            //$lazyAttribute = $lazy ? ' loading="lazy"' : '';

            $alt = $altText ?: (!empty($meta['alt']) ? $meta['alt'] : (!empty($headline) ? $headline : (!empty($caption) ? $caption : '')));
            $title = !empty($meta['title']) ? $meta['title'] : (!empty($headline) ? $headline : (!empty($caption) ? $caption : ''));
            $finalCaption = $caption ?: (!empty($meta['caption']) ? $meta['caption'] : '');
            $finalLink = $imageUrl ?: (!empty($meta['link']) ? $meta['link'] : '');

            $imgTag = '<picture>' . implode("\n", $sources);
            $imgTag .= sprintf('<img %s data-src="%s" data-srcset="%s" sizes="%s" alt="%s" %s loading="lazy">',
                $classAttribute,
                $imageSrc,
                implode(', ', $srcset),
                implode(', ', $sizes) . ', 100vw',
                htmlspecialchars($alt),
                $title ? ' title="' . htmlspecialchars($title) . '"' : '',
                //$lazyAttribute
            );
            $imgTag .= '</picture>';

            $finalOutput = '<figure>' . $imgTag;
            if ($inSlider) {
                $finalOutput .= '<div class="swiper-lazy-preloader"></div>';
                if ($finalCaption) {
                    $finalOutput .= '<div class="slider-caption">' . htmlspecialchars($finalCaption) . '</div>';
                }
                $finalOutput = str_replace(["data-src", "data-srcset"], ["src", "srcset"], $finalOutput);
            } elseif ($finalCaption) {
                $finalOutput .= '<figcaption>' . htmlspecialchars($finalCaption) . '</figcaption>';
            }
            $finalOutput .= '</figure>';

            if ($finalLink || $colorBox) {
                $linkAttributes = [
                    'href' => $finalLink ?: $lightboxImageSrc,
                    'title' => $title ? htmlspecialchars($title) : null,
                    'target' => $finalLink && $colorBox ? '_blank' : null,
                    'data-gall' => $colorBox ? "group_" . htmlspecialchars($colorBox) : null,
                    'class' => $colorBox ? "lightbox_" . htmlspecialchars($colorBox) : null
                ];
                $linkAttributesString = implode(' ', array_filter(array_map(
                    function ($key, $value) {
                        return $value !== null ? $key . '="' . $value . '"' : null;
                    },
                    array_keys($linkAttributes), $linkAttributes
                )));
                $finalOutput = "<a {$linkAttributesString}>{$finalOutput}</a>";
            }

            if (!$lazy) {
                $finalOutput = str_replace(["data-src", "data-srcset"], ["src", "srcset"], $finalOutput);
                $finalOutput = preg_replace('/\sclass="lazy(.*?)"/', ' class="$1"', $finalOutput);
                $finalOutput = str_replace('class=" "', '', $finalOutput); // Entferne leere class-Attribute
            }

            return $finalOutput;
        }
    }

    private static function getRelativeImagePath($rootDir, $imagePath)
    {
        $relativePath = str_replace($rootDir, '', $imagePath);
        return dirname($relativePath) . '/' . rawurlencode(basename($relativePath));
    }


// Neue Funktion für die Bildoptimierung
    private static function optimizeImage($imagePath)
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        if ($extension === 'jpg' || $extension === 'jpeg') {
            if (function_exists('imagecreatefromjpeg')) {
                $image = @imagecreatefromjpeg($imagePath);
                if ($image === false) {
                    //error_log("Failed to create image from JPEG: $imagePath");
                    return;
                }
                $result = imagejpeg($image, $imagePath, 85);
                if ($result === false) {
                    //error_log("Failed to save optimized JPEG: $imagePath");
                }
                imagedestroy($image);
            } else {
                //error_log("imagecreatefromjpeg function not available");
            }
        } elseif ($extension === 'png') {
            if (function_exists('imagecreatefrompng')) {
                $image = @imagecreatefrompng($imagePath);
                if ($image === false) {
                    //error_log("Failed to create image from PNG: $imagePath");
                    return;
                }
                // Erhalte Transparenz
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $result = imagepng($image, $imagePath, 6); // Reduzierte Kompression für bessere Qualität
                if ($result === false) {
                    //error_log("Failed to save optimized PNG: $imagePath");
                }
                imagedestroy($image);
            } else {
                //error_log("imagecreatefrompng function not available");
            }
        } else {
            //error_log("Unsupported image format for optimization: $extension");
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
