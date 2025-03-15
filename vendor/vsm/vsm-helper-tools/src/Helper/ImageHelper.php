<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */
namespace Vsm\VsmHelperTools\Helper;

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


    private static function handleImageFormat(string $imagePath): array
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $standardFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if ($extension === 'svg') {
            return [
                'type' => 'svg',
                'path' => $imagePath
            ];
        }

        if (in_array($extension, $standardFormats)) {
            return [
                'type' => 'standard',
                'path' => $imagePath
            ];
        }

        // Versuche Konvertierung für unbekannte Formate
        $converted = self::convertToJpeg($imagePath);
        if ($converted) {
            return [
                'type' => 'converted',
                'path' => $converted['path']
            ];
        }

        // Wenn Konvertierung fehlschlägt, als unknown markieren
        return [
            'type' => 'unknown',
            'path' => $imagePath
        ];
    }

    private static function handleUnknownFormat($imagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy): string
    {
        // SVG direkt ausgeben
        if (strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)) === 'svg') {
            return self::handleSvg($imagePath, $rootDir, $altText, $meta, $headline, [], $class);
        }

        $imageSrc = str_replace($rootDir, '', $imagePath);
        $imageSrc = self::encodePath($imageSrc);

        $alt = self::cleanAttribute($altText ?: (!empty($meta['alt']) ? $meta['alt'] : (!empty($headline) ? $headline : '')));
        $title = self::cleanAttribute(!empty($meta['title']) ? $meta['title'] : (!empty($headline) ? $headline : ''));

        // Picture Tag mit Original-Bild
        $imgTag = '<picture>';
        $imgTag .= sprintf(
            '<img %ssrc="%s" alt="%s"%s%s%s>',
            $lazy ? 'data-' : '',
            $imageSrc,
            $alt,
            $class ? ' class="' . ($lazy ? 'lazy ' : '') . htmlspecialchars($class) . '"' : ($lazy ? ' class="lazy"' : ''),
            $title ? ' title="' . htmlspecialchars($title) . '"' : '',
            $lazy ? ' loading="lazy"' : ''
        );
        $imgTag .= '</picture>';

        $finalOutput = '<figure>' . $imgTag;
        if ($caption) {
            $finalOutput .= '<figcaption>' . htmlspecialchars($caption) . '</figcaption>';
        }
        $finalOutput .= '</figure>';

        return $finalOutput;
    }

    private static function convertToJpeg(string $imagePath): ?array
    {
        try {
            $container = System::getContainer();
            $projectDir = $container->getParameter('kernel.project_dir');
            $logger = $container->get('monolog.logger.contao');

            if (!file_exists($imagePath)) {
                $logger->error('Source file does not exist: ' . $imagePath);
                return null;
            }

            $targetDir = $projectDir . '/assets/images/converted';
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    $logger->error('Failed to create target directory: ' . $targetDir);
                    return null;
                }
            }

            $baseName = pathinfo($imagePath, PATHINFO_FILENAME);
            $targetPath = $targetDir . '/' . $baseName . '_' . uniqid() . '.jpg';

            if (extension_loaded('imagick')) {
                try {
                    $image = new \Imagick();
                    $image->readImage($imagePath);
                    $image->setImageFormat('jpeg');
                    $image->setImageCompressionQuality(self::DEFAULT_QUALITY);
                    $image->writeImage($targetPath);
                    $image->clear();
                    $image->destroy();

                    if (file_exists($targetPath)) {
                        $logger->info('Successfully converted to: ' . $targetPath);
                        return [
                            'path' => $targetPath,
                            'src' => str_replace($projectDir, '', $targetPath)
                        ];
                    }
                } catch (\ImagickException $e) {
                    $logger->notice('Conversion failed for ' . $imagePath . ': ' . $e->getMessage());
                    return null;
                }
            }

            return null;

        } catch (\Exception $e) {
            if (isset($logger)) {
                $logger->error(
                    'Image conversion exception: ' . $e->getMessage(),
                    ['file' => $imagePath, 'trace' => $e->getTraceAsString()]
                );
            }
            return null;
        }
    }

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
            $retinaConfig->setHeight((int)($baseConfig->getHeight() * $factor));

            // Wenn eine Höhe gesetzt ist, diese auch anpassen
            if ($baseConfig->getHeight()) {
                $retinaConfig->setHeight((int)($baseConfig->getHeight() * $factor));
            }

            return $retinaConfig;
        }
        return null;
    }

    private static function generateSource(string $type, string $src, ?string $retinaSrc = null, ?string $retina3xSrc = null, ?string $mediaQuery = null): string
    {
        // Nur vollständige Bildpfade verwenden
        $srcsetParts = [];

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|heic)$/i', $src)) {
            $srcsetParts[] = $src . ' 1x';
        }

        if ($retinaSrc && preg_match('/\.(jpg|jpeg|png|gif|webp|heic)$/i', $retinaSrc)) {
            $srcsetParts[] = $retinaSrc . ' 2x';
        }

        if ($retina3xSrc && preg_match('/\.(jpg|jpeg|png|gif|webp|heic)$/i', $retina3xSrc)) {
            $srcsetParts[] = $retina3xSrc . ' 3x';
        }

        $srcset = implode(', ', $srcsetParts);

        return empty($srcset) ? '' : sprintf(
            '<source type="%s" data-srcset="%s"%s>',
            $type,
            self::cleanAttribute($srcset),
            $mediaQuery ? ' media="' . $mediaQuery . '"' : ''
        );
    }

    private static function cleanAttribute($str): string
    {
        if (empty($str)) {
            return '';
        }

        // Explizit <wbr> in allen Varianten entfernen (vor strip_tags)
        $str = preg_replace('/<wbr\s*\/?>/i', '', $str);

        // HTML-Tags und Entities vollständig entfernen
        $str = strip_tags($str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Soft hyphens (weiche Bindestriche) entfernen
        $str = str_replace(["\xC2\xAD", "­", "<wbr>", "<", ">"], '', $str);

        // Mehrfache Leerzeichen durch einzelnes ersetzen
        $str = preg_replace('/\s+/', ' ', trim($str));

        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    private static function processImage(string $path, ResizeConfiguration $config, ResizeOptions $options): array
    {
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');

        $processedImage = $imageFactory->create($path, $config, $options);
        $processedPath = $processedImage->getPath();

        // Vollständigen relativen Pfad erstellen
        $relativePath = str_replace($rootDir, '', $processedPath);

        // Sicherstellen, dass der Pfad mit .jpg, .png etc. endet
        if (!preg_match('/\.(jpg|jpeg|png|gif|webp|heic)$/i', $relativePath)) {
            return ['path' => $processedPath, 'src' => ''];
        }

        // URL-Encode jedes Verzeichnis-Segment einzeln
        $pathParts = explode('/', $relativePath);
        $encodedParts = array_map('rawurlencode', $pathParts);
        $encodedPath = implode('/', $encodedParts);

        return [
            'path' => $processedPath,
            'src' => $encodedPath
        ];
    }

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

            // Prüfen ob $imageMeta ein Array ist
            if (is_array($imageMeta) && !empty($imageMeta)) {
                $currentMeta = isset($imageMeta[$currentLanguage]) ? $imageMeta[$currentLanguage] : reset($imageMeta);
                if (is_array($currentMeta)) {
                    foreach ($currentMeta as $key => $value) {
                        $meta[$key] = self::cleanAttribute($value);
                    }
                }
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
            // return '';
        }

// NEU:
        $imageFormat = self::handleImageFormat($baseImagePath);

        switch ($imageFormat['type']) {
            case 'svg':
                return self::handleSvg($baseImagePath, $rootDir, $altText, $meta, $headline, $size, $class);
            case 'unknown':
                return self::handleUnknownFormat($baseImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy);
            default:
                $baseImagePath = $imageFormat['path'];
                $absoluteImagePath = $imageFormat['path'];
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
// Basiskonfiguration
        $config = new ResizeConfiguration();
        if ($size) {
            // Falls size als serialisierter String übergeben wurde
            if (is_string($size) && strpos($size, 'a:') === 0) {
                $size = StringUtil::deserialize($size);
            }

            // Prüfen ob das Array tatsächlich Werte enthält
            if (is_array($size)) {
                $requestedWidth = !empty($size[0]) ? (int)$size[0] : null;
                $requestedHeight = !empty($size[1]) ? (int)$size[1] : null;
                $mode = !empty($size[2]) ? $size[2] : "proportional";

                // Grundkonfiguration mit den ursprünglich gewünschten Maßen
                if ($requestedWidth) {
                    $config->setWidth($requestedWidth);
                }
                if ($requestedHeight) {
                    $config->setHeight($requestedHeight);
                }
                if ($mode) {
                    $config->setMode($mode);
                }

                // Basisbreite für Breakpoints setzen
                $baseWidth = $requestedWidth ?? ($requestedHeight ? round($originalWidth * ($requestedHeight / $originalHeight)) : $originalWidth);
                $baseHeight = $requestedHeight ?? ($requestedWidth ? round($originalHeight * ($requestedWidth / $originalWidth)) : $originalHeight);
            } else {
                // Bei leeren Werten setzen wir die Originalmaße und proportionalen Modus
                $config->setMode("proportional");
                $baseWidth = $originalWidth;
                $baseHeight = $originalHeight;
            }
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
                $height = (int)round($width * $ratio);
                $config->setWidth((int)$width);
                $config->setHeight((int)$height);
                $config->setMode($mode);
            } else {
                $config->setWidth((int)$width);
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
$title = self::cleanAttribute(!empty($meta['title']) ? $meta['title'] : (!empty($headline) ? $headline : (!empty($caption) ? $caption : (!empty($alt) ? $alt : ''))));
        $finalCaption = self::cleanAttribute($caption ?: (!empty($meta['caption']) ? $meta['caption'] : ''));
        $finalLink = self::cleanAttribute($imageUrl ?: (!empty($meta['link']) ? $meta['link'] : ''));

        // Picture Tag zusammenbauen
        $imgTag = '<picture>' . implode("\n", $sources);
        $imgTag .= sprintf(
            '<img %s data-src="%s" data-srcset="%s" sizes="%s" alt="%s" %s loading="lazy">',
            $classAttribute,
            self::cleanAttribute($imageSrc),
            self::formatSrcset($srcset), // Neue Hilfsmethode verwenden
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

    private static function formatSrcset(array $srcset): string
    {
        // Filtere ungültige Einträge und stelle korrektes Format sicher
        $validSrcset = array_filter($srcset, function ($entry) {
            // Prüfe auf vollständigen Bildpfad und korrektes Format
            return preg_match('/\.(jpg|jpeg|png|gif|webp|heic)\s+\d+w$/i', $entry);
        });

        // Entferne doppelte Einträge
        $validSrcset = array_unique($validSrcset);

        return self::cleanAttribute(implode(', ', $validSrcset));
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

    public static function generateImageURL($imageSource, array|string|null $size = null): string
    {
        if (!$imageObject = FilesModel::findByUuid($imageSource)) {
            return '';
        }

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $absolutePath = $rootDir . '/' . urldecode($imageObject->path);

        // Prüfe auf nicht-Standard-Format
        $imageFormat = self::handleImageFormat($absolutePath);

        switch ($imageFormat['type']) {
            case 'svg':
            case 'unknown':
                return self::encodePath(str_replace($rootDir, '', $absolutePath));
            default:
                $absolutePath = $imageFormat['path'];
        }

        $config = new ResizeConfiguration();
        if ($size) {
            // Falls size als serialisierter String übergeben wurde
            if (is_string($size) && strpos($size, 'a:') === 0) {
                $size = StringUtil::deserialize($size);
            }

            // Prüfen ob das Array tatsächlich Werte enthält
            if (is_array($size) && (!empty($size[0]) || !empty($size[1]))) {
                $width = !empty($size[0]) ? (int)$size[0] : null;
                $height = !empty($size[1]) ? (int)$size[1] : null;
                $mode = !empty($size[2]) ? $size[2] : 'proportional';

                if ($width) $config->setWidth($width);
                if ($height) $config->setHeight($height);
                if ($mode) $config->setMode($mode);
            } else {
                // Bei leeren Werten setzen wir nur den proportionalen Modus
                $config->setMode("proportional");
            }
        }

        try {
            $processedImage = System::getContainer()
                ->get('contao.image.factory')
                ->create(
                    $absolutePath,
                    $config,
                    self::getResizeOptions()
                );
            $path = str_replace($rootDir, "", $processedImage->getPath());
            return self::encodePath($path);
        } catch (\Exception $e) {
            return '';
        }
    }

    public static function getSvgCode($uuid, $alt = '', $size = null, $classes = ''): string
    {
        if (!$fileModel = FilesModel::findByUuid($uuid)) {
            return '';
        }

        $projectDir = System::getContainer()->getParameter('kernel.project_dir');
        $fullPath = $projectDir . '/' . $fileModel->path;

        if (!file_exists($fullPath)) {
            return '';
        }

        $svgContent = file_get_contents($fullPath);
        if (!$svgContent) {
            return '';
        }

        // Style basierend auf Größe
        $style = '';
        if (is_array($size) && !empty($size[0])) {
            $style = 'width: ' . $size[0] . 'px;';
        }

        // Basis-Klassen für SVG
        $baseClasses = 'svg-image ' . $classes;

        // SVG modifizieren für bessere Farbsteuerung
        $svgContent = preg_replace('/<svg /', '<svg class="' . $baseClasses . '" style="' . $style . '" ', $svgContent, 1);

        // Entferne eventuell vorhandene fill-Attribute
        $svgContent = preg_replace('/fill="[^"]*"/', '', $svgContent);

        // Füge title für Barrierefreiheit hinzu, wenn noch nicht vorhanden
        if (!strpos($svgContent, '<title>') && $alt) {
            $svgContent = preg_replace('/<svg ([^>]*)>/', '<svg $1><title>' . htmlspecialchars($alt) . '</title>', $svgContent);
        }

        return $svgContent;
    }
}