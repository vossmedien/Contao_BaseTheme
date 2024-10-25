<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
use Contao\Image\ResizeConfiguration;
use Contao\Image\ResizeOptions;

class ImageHelper
{
    private const DEFAULT_QUALITY = 85;
    private const PNG_COMPRESSION = 6;
    private const BREAKPOINTS = [
        ['maxWidth' => 576, 'width' => 576],
        ['maxWidth' => 768, 'width' => 768],
        ['maxWidth' => 992, 'width' => 992],
        ['maxWidth' => 1200, 'width' => 1200],
        ['maxWidth' => 1600, 'width' => 1600]
    ];

    private static function getResizeOptions($format = null): ResizeOptions
    {
        $options = new ResizeOptions();
        $baseOptions = [
            'quality' => self::DEFAULT_QUALITY,
            'png_compression_level' => self::PNG_COMPRESSION
        ];

        if ($format) {
            $baseOptions['format'] = $format;
        }

        $options->setImagineOptions($baseOptions);
        return $options;
    }

    private static function getRetinaConfig(ResizeConfiguration $baseConfig, int $width, int $originalWidth, int $factor = 2): ?ResizeConfiguration
    {
        if ($width * $factor <= $originalWidth) {
            $retinaConfig = clone $baseConfig;
            $retinaConfig->setWidth($width * $factor);

            // Wenn eine Höhe gesetzt ist, diese auch anpassen
            if ($baseConfig->getHeight()) {
                $retinaConfig->setHeight($baseConfig->getHeight() * $factor);
            }

            return $retinaConfig;
        }
        return null;
    }

    private static function generateSource(string $type, string $src, ?string $retinaSrc = null, ?string $retina3xSrc = null, ?string $mediaQuery = null): string
    {
        $srcset = $src . ' 1x';
        if ($retinaSrc) {
            $srcset .= ', ' . $retinaSrc . ' 2x';
        }
        if ($retina3xSrc) {
            $srcset .= ', ' . $retina3xSrc . ' 3x';
        }

        return sprintf(
            '<source type="%s" data-srcset="%s"%s>',
            $type,
            $srcset,
            $mediaQuery ? ' media="' . $mediaQuery . '"' : ''
        );
    }

    private static function cleanAttribute($str): string
    {
        if (empty($str)) {
            return '';
        }

        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $str = preg_replace('/\s+/', ' ', trim($str));

        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    private static function processImage(string $path, ResizeConfiguration $config, ResizeOptions $options): array
    {
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');

        // Pfad für die Verarbeitung dekodieren
        $decodedPath = urldecode($path);

        $processedImage = $imageFactory->create($decodedPath, $config, $options);
        $processedPath = $processedImage->getPath();

        $relativePath = str_replace($rootDir, '', $processedPath);

        return [
            'path' => $processedPath,
            'src' => ltrim($relativePath, '/')  // Führenden Slash entfernen
        ];
    }

    public static function generateImageHTML(
        $imageSource,
        ?string $altText = '',
        ?string $headline = '',
        ?array $size = null,
        ?string $class = '',
        bool $inSlider = false,
        $colorBox = false,
        bool $lazy = true,
        ?string $caption = '',
        ?string $imageUrl = ''
    ): string
    {
        // Null-Werte in leere Strings umwandeln
        $altText = $altText ?? '';
        $headline = $headline ?? '';
        $class = $class ?? '';
        $caption = $caption ?? '';
        $imageUrl = $imageUrl ?? '';

        if (empty($imageSource)) {
            return '';
        }

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? System::getContainer()->getParameter('kernel.default_locale');
        $originalWidth = $originalHeight = 0;

        // Metadaten verarbeiten
        if ($imageObject = FilesModel::findByUuid($imageSource)) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = [];
            foreach ($imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [] as $key => $value) {
                $meta[$key] = self::cleanAttribute($value);
            }
            $relativeImagePath = $imageObject->path;
        } else {
            $relativeImagePath = $imageSource;
            $meta = [];
        }

        $absoluteImagePath = $rootDir . '/' . urldecode($relativeImagePath);
        $baseImagePath = str_replace('\\', '/', urldecode($absoluteImagePath));

// Für file_exists den dekodierten Pfad verwenden
        if (!file_exists($baseImagePath) ||
            !($originalImageInfo = @getimagesize($baseImagePath)) ||
            !is_array($originalImageInfo)) {
            return '';
        }

        // SVG Handling
        if (strtolower(pathinfo($baseImagePath, PATHINFO_EXTENSION)) === 'svg') {
            return self::handleSvg($baseImagePath, $rootDir, $altText, $meta, $headline, $size, $class);
        }

        // Originalbild-Dimensionen prüfen
        if (!file_exists($baseImagePath) ||
            !($originalImageInfo = @getimagesize($baseImagePath)) ||
            !is_array($originalImageInfo)) {
            return '';
        }

        $originalWidth = (int)$originalImageInfo[0];
        $originalHeight = (int)$originalImageInfo[1];

        // Basiskonfiguration
        $config = new ResizeConfiguration();
        if ($size && is_array($size)) {
            $requestedWidth = isset($size[0]) && $size[0] !== '' ? (int)$size[0] : null;
            $requestedHeight = isset($size[1]) && $size[1] !== '' ? (int)$size[1] : null;
            $mode = $size[2] ?? "proportional";

            // Grundkonfiguration mit den ursprünglich gewünschten Maßen
            if ($requestedWidth) $config->setWidth($requestedWidth);
            if ($requestedHeight) $config->setHeight($requestedHeight);
            if ($mode) $config->setMode($mode);

            // Basisbreite für Breakpoints setzen
            $baseWidth = $requestedWidth;
            $baseHeight = $requestedHeight;
        }

        try {
            $baseImage = self::processImage(
                $absoluteImagePath,
                $config,
                self::getResizeOptions()
            );
            $baseWidth = $baseWidth ?? $originalWidth;
            $baseHeight = $baseHeight ?? $originalHeight;
        } catch (\Exception $e) {
            $baseWidth = $originalWidth;
            $baseHeight = $originalHeight;
            return '';
        }

        // Breakpoints filtern
        $validBreakpoints = array_filter(self::BREAKPOINTS, function ($breakpoint) use ($baseWidth) {
            return $breakpoint['width'] <= $baseWidth;
        });
        $validBreakpoints[] = ['maxWidth' => null, 'width' => $baseWidth];

        $sources = [];
        $processedSrcsets = [];
        $srcset = [];
        $webpSrcset = [];
        $sizes = [];
        foreach ($validBreakpoints as $breakpoint) {
            $config = new ResizeConfiguration();
            $width = (int)$breakpoint['width'];
            $mode = $size[2] ?? "proportional";

            if ($width > $baseWidth) {
                continue;
            }

            // Konfiguration für Crop-Mode
            if ($mode === 'crop' && !empty($size[0]) && !empty($size[1])) {
                $ratio = $size[1] / $size[0];
                $height = round($width * $ratio);
                $config->setWidth($width);
                $config->setHeight($height);
                $config->setMode($mode);
            } else {
                $config->setWidth($width);
                if ($mode !== "") {
                    $config->setMode($mode);
                }
            }

            try {
                // Normales Bild
                $processedImage = self::processImage(
                    $absoluteImagePath,
                    $config,
                    self::getResizeOptions()
                );

                // WebP Version
                $webpImage = self::processImage(
                    $absoluteImagePath,
                    $config,
                    self::getResizeOptions('webp')
                );

                $imageSrc = self::encodePath($processedImage['src']);
                $webpSrc = self::encodePath($webpImage['src']);

                $srcset[] = $imageSrc . ' ' . $width . 'w';
                $webpSrcset[] = $webpSrc . ' ' . $width . 'w';

                // Retina Versionen
                $retinaImageSrc = $imageSrc;
                $retinaWebpSrc = $webpSrc;
                $retina3xImageSrc = $imageSrc;
                $retina3xWebpSrc = $webpSrc;

                // 2x Retina nur wenn das Originalbild mindestens doppelt so groß ist
                if ($width * 2 <= $originalWidth) {
                    if ($retinaConfig = self::getRetinaConfig($config, $width, $originalWidth, 2)) {
                        $retina2xImage = self::processImage(
                            $absoluteImagePath,
                            $retinaConfig,
                            self::getResizeOptions()
                        );
                        $retina2xWebp = self::processImage(
                            $absoluteImagePath,
                            $retinaConfig,
                            self::getResizeOptions('webp')
                        );

                        $retinaImageSrc = $retina2xImage['src'];
                        $retinaWebpSrc = $retina2xWebp['src'];
                        $srcset[] = $retinaImageSrc . ' ' . ($width * 2) . 'w';
                        $webpSrcset[] = $retinaWebpSrc . ' ' . ($width * 2) . 'w';
                    }
                }

// 3x Retina für mobile nur wenn das Originalbild mindestens dreimal so groß ist
                if ($width <= 768 && $width * 3 <= $originalWidth) {
                    if ($retinaConfig = self::getRetinaConfig($config, $width, $originalWidth, 3)) {
                        $retina3xImage = self::processImage(
                            $absoluteImagePath,
                            $retinaConfig,
                            self::getResizeOptions()
                        );
                        $retina3xWebp = self::processImage(
                            $absoluteImagePath,
                            $retinaConfig,
                            self::getResizeOptions('webp')
                        );

                        $retina3xImageSrc = $retina3xImage['src'];
                        $retina3xWebpSrc = $retina3xWebp['src'];
                        $srcset[] = $retina3xImageSrc . ' ' . ($width * 3) . 'w';
                        $webpSrcset[] = $retina3xWebpSrc . ' ' . ($width * 3) . 'w';
                    }
                }

                // Sizes und Sources generieren
                if ($breakpoint['maxWidth']) {
                    $sizes[] = '(max-width: ' . $breakpoint['maxWidth'] . 'px) ' . $width . 'px';
                } else {
                    $sizes[] = $width . 'px';
                }

                if ($breakpoint['maxWidth'] && !in_array($imageSrc, $processedSrcsets)) {
                    $mediaQuery = "(max-width: {$breakpoint['maxWidth']}px)";
                    if ($width <= 768) {
                        $has3x = $width * 3 <= $originalWidth;
                        $has2x = $width * 2 <= $originalWidth;

                        $sources[] = self::generateSource(
                            "image/webp",
                            $webpSrc,
                            $has2x ? $retinaWebpSrc : null,
                            $has3x ? $retina3xWebpSrc : null,
                            $mediaQuery
                        );
                        $sources[] = self::generateSource(
                            "image/jpeg",
                            $imageSrc,
                            $has2x ? $retinaImageSrc : null,
                            $has3x ? $retina3xImageSrc : null,
                            $mediaQuery
                        );
                    } else {
                        $has2x = $width * 2 <= $originalWidth;

                        $sources[] = self::generateSource(
                            "image/webp",
                            $webpSrc,
                            $has2x ? $retinaWebpSrc : null,
                            null,
                            $mediaQuery
                        );
                        $sources[] = self::generateSource(
                            "image/jpeg",
                            $imageSrc,
                            $has2x ? $retinaImageSrc : null,
                            null,
                            $mediaQuery
                        );
                    }
                    $processedSrcsets[] = $imageSrc;
                } elseif (!$breakpoint['maxWidth']) {
                    $has2x = $width * 2 <= $originalWidth;

                    $sources[] = self::generateSource(
                        "image/webp",
                        $webpSrc,
                        $has2x ? $retinaWebpSrc : null
                    );
                    $sources[] = self::generateSource(
                        "image/jpeg",
                        $imageSrc,
                        $has2x ? $retinaImageSrc : null
                    );
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        // Lightbox Image
        $lightboxImageSrc = $imageSrc;
        if ($colorBox && ($originalWidth > 1200 || $originalHeight > 1200)) {
            $lightboxConfig = new ResizeConfiguration();
            $lightboxConfig->setWidth(1200)->setHeight(1200)->setMode('box');
            try {
                $lightboxImage = self::processImage($absoluteImagePath, $lightboxConfig, self::getResizeOptions());
                $lightboxImageSrc = $lightboxImage['src'];
            } catch (\Exception $e) {
                // Fallback to original image
            }
        }

        // HTML Generierung
        $classAttribute = $inSlider
            ? ($class ? ' class="' . htmlspecialchars($class) . '"' : '')
            : ($class ? ' class="lazy ' . htmlspecialchars($class) . '"' : ' class="lazy"');

        // Attribute vorbereiten
        $alt = self::cleanAttribute($altText ?: (!empty($meta['alt']) ? $meta['alt'] : (!empty($headline) ? $headline : (!empty($caption) ? $caption : ''))));
        $title = self::cleanAttribute(!empty($meta['title']) ? $meta['title'] : (!empty($headline) ? $headline : (!empty($caption) ? $caption : '')));
        $finalCaption = self::cleanAttribute($caption ?: (!empty($meta['caption']) ? $meta['caption'] : ''));
        $finalLink = self::cleanAttribute($imageUrl ?: (!empty($meta['link']) ? $meta['link'] : ''));

        // Picture Tag zusammenbauen
        $imgTag = '<picture>' . implode("\n", $sources);
        $imgTag .= sprintf(
            '<img %s data-src="%s" data-srcset="%s" sizes="%s" alt="%s" %s loading="lazy">',
            $classAttribute,
            self::cleanAttribute($imageSrc),
            self::cleanAttribute(implode(', ', $srcset)),
            self::cleanAttribute(implode(', ', $sizes) . ', 100vw'),
            $alt,
            $title ? ' title="' . $title . '"' : ''
        );
        $imgTag .= '</picture>';

        // Figure Tag erstellen
        $finalOutput = '<figure>' . $imgTag;
        if ($inSlider) {
            $finalOutput .= '';
            if ($finalCaption) {
                $finalOutput .= '<div class="slider-caption">' . $finalCaption . '</div>';
            }
            $finalOutput = str_replace(["data-src", "data-srcset"], ["src", "srcset"], $finalOutput);
        } elseif ($finalCaption) {
            $finalOutput .= '<figcaption>' . $finalCaption . '</figcaption>';
        }
        $finalOutput .= '</figure>';

        // Link Handling
        if ($finalLink || $colorBox) {
            $linkAttributes = [
                'href' => self::cleanAttribute($finalLink ?: $lightboxImageSrc),
                'title' => $title ?: null,
                'target' => $finalLink && $colorBox ? '_blank' : null,
                'data-gall' => $colorBox ? "group_" . self::cleanAttribute($colorBox) : null,
                'class' => $colorBox ? "lightbox_" . self::cleanAttribute($colorBox) : null
            ];

            $linkAttributesString = implode(' ', array_filter(array_map(
                function ($key, $value) {
                    return $value !== null ? $key . '="' . $value . '"' : null;
                },
                array_keys($linkAttributes),
                $linkAttributes
            )));

            $finalOutput = "<a {$linkAttributesString}>{$finalOutput}</a>";
        }

        // Lazy Loading Handling
        if (!$lazy) {
            $finalOutput = str_replace(["data-src", "data-srcset"], ["src", "srcset"], $finalOutput);
            $finalOutput = preg_replace('/\sclass="lazy(.*?)"/', ' class="$1"', $finalOutput);
            $finalOutput = str_replace('class=" "', '', $finalOutput);
        }

        return $finalOutput;
    }

    private static function encodePath(string $path): string
    {
        // Sicherstellen, dass der Pfad mit einem Slash beginnt
        $path = ltrim($path, '/');

        // Pfad in Segmente aufteilen
        $segments = explode('/', $path);

        // Letztes Segment (Dateiname) separat behandeln
        $fileName = array_pop($segments);

        // Verzeichnispfad segmentweise kodieren
        $encodedSegments = array_map(function ($segment) {
            return rawurlencode(urldecode($segment));
        }, $segments);

        // Dateinamen kodieren
        $encodedFileName = rawurlencode(urldecode($fileName));

        // Alles wieder zusammenfügen
        return '/' . implode('/', $encodedSegments) . '/' . $encodedFileName;
    }

    private static function handleSvg($baseImagePath, $rootDir, $altText, $meta, $headline, $size, $class): string
    {
        $imageSrc = str_replace($rootDir, '', $baseImagePath);
        $imageSrc = self::encodePath($imageSrc);

        $alt = self::cleanAttribute($altText ?: (!empty($meta['alt']) ? $meta['alt'] : (!empty($headline) ? $headline : 'SVG Bild')));

        $style = '';
        if ($size && is_array($size)) {
            $width = isset($size[0]) && $size[0] !== '' ? (int)$size[0] : null;
            $height = isset($size[1]) && $size[1] !== '' ? (int)$size[1] : null;
            if ($width) $style .= "width: {$width}px; ";
            if ($height) $style .= "height: {$height}px; ";
        }

        $svgTag = sprintf(
            '<img data-src="%s" alt="%s" class="lazy %s"%s>',
            $imageSrc,
            $alt,
            htmlspecialchars($class),
            $style ? ' style="' . htmlspecialchars($style) . '"' : ''
        );

        return '<figure>' . $svgTag . '</figure>';
    }

    public static function generateImageURL($imageSource, $size = null): string
    {
        if (!$imageObject = FilesModel::findByUuid($imageSource)) {
            return '';
        }

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $imageFactory = System::getContainer()->get('contao.image.factory');

        $config = new ResizeConfiguration();
        if ($size && is_array($size)) {
            $width = !empty($size[0]) ? (int)$size[0] : null;
            $height = !empty($size[1]) ? (int)$size[1] : null;
            $mode = !empty($size[2]) ? $size[2] : 'proportional';

            if ($width) $config->setWidth($width);
            if ($height) $config->setHeight($height);
            if ($mode) $config->setMode($mode);
        }

        try {
            $processedImage = $imageFactory->create(
                $rootDir . '/' . urldecode($imageObject->path),
                $config,
                self::getResizeOptions()
            );
            $path = str_replace($rootDir, "", $processedImage->getPath());
            return self::encodePath($path);
        } catch (\Exception $e) {
            return '';
        }
    }
}