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
use Contao\CoreBundle\Util\StringUtil as ContaoStringUtil;
use Symfony\Component\Uid\Uuid;

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

    // Cache für verarbeitete Bilder
    private static $processedImagesCache = [];
    private static $processedImagesCacheSize = 0;
    private static $maxCacheSize = 100; // Maximale Anzahl von Bildern im Cache

    // Cache für Bildformate
    private static $imageFormatCache = [];
    private static $imageFormatCacheSize = 0;
    private static $maxFormatCacheSize = 100; // Maximale Anzahl von Formaten im Cache

    // Cache für Bildgrößen-Auflösung
    private static $sizeConfigCache = [];

    // Container Cache für Performance
    private static $container = null;

    /**
     * Optimierter Container-Zugriff
     */
    private static function getContainer()
    {
        return self::$container ??= System::getContainer();
    }

    /**
     * Cache-Management für alle Caches
     */
    private static function clearCacheIfNeeded(): void
    {
        if (self::$processedImagesCacheSize >= self::$maxCacheSize) {
            self::$processedImagesCache = [];
            self::$processedImagesCacheSize = 0;
        }
        
        if (self::$imageFormatCacheSize >= self::$maxFormatCacheSize) {
            self::$imageFormatCache = [];
            self::$imageFormatCacheSize = 0;
        }
    }

    private static function handleImageFormat(string $imagePath): array
    {
        // Aus Cache laden, wenn vorhanden
        $cacheKey = md5($imagePath);
        if (isset(self::$imageFormatCache[$cacheKey])) {
            return self::$imageFormatCache[$cacheKey];
        }

        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        $standardFormats = ['jpg', 'jpeg', 'png', 'gif'];

        // MIME-Type überprüfen, wenn möglich
        $mimeType = null;
        if (function_exists('mime_content_type') && file_exists($imagePath)) {
            $mimeType = mime_content_type($imagePath);
        } elseif (function_exists('finfo_open') && file_exists($imagePath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $imagePath);
            finfo_close($finfo);
        }

        // Überprüfung anhand MIME-Type und Dateiendung
        if ($extension === 'svg' || ($mimeType && strpos($mimeType, 'image/svg') !== false)) {
            $result = [
                'type' => 'svg',
                'path' => $imagePath
            ];

            self::addToImageFormatCache($cacheKey, $result);
            return $result;
        }

        if ($extension === 'avif' || ($mimeType && $mimeType === 'image/avif')) {
            $result = [
                'type' => 'avif',
                'path' => $imagePath
            ];

            self::addToImageFormatCache($cacheKey, $result);
            return $result;
        }

        if ($extension === 'webp' || ($mimeType && $mimeType === 'image/webp')) {
            $result = [
                'type' => 'webp',
                'path' => $imagePath
            ];

            self::addToImageFormatCache($cacheKey, $result);
            return $result;
        }

        if (in_array($extension, $standardFormats) ||
            ($mimeType && in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif']))) {
            $result = [
                'type' => 'standard',
                'path' => $imagePath
            ];

            self::addToImageFormatCache($cacheKey, $result);
            return $result;
        }

        // Versuche Konvertierung für unbekannte Formate
        $converted = self::convertToJpeg($imagePath);
        if ($converted) {
            $result = [
                'type' => 'converted',
                'path' => $converted['path']
            ];

            self::addToImageFormatCache($cacheKey, $result);
            return $result;
        }

        // Wenn Konvertierung fehlschlägt, als unknown markieren
        $result = [
            'type' => 'unknown',
            'path' => $imagePath
        ];

        self::addToImageFormatCache($cacheKey, $result);
        return $result;
    }

    /**
     * Hilfsmethode zum Hinzufügen zum Format-Cache mit Größenbeschränkung
     */
    private static function addToImageFormatCache($key, $value): void
    {
        self::clearCacheIfNeeded();
        self::$imageFormatCache[$key] = $value;
        self::$imageFormatCacheSize++;
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
            $container = self::getContainer();
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
            // Explizite Format-Angabe für korrekte Konvertierung
            if ($format === 'jpeg' || $format === 'jpg') {
                $baseOptions['format'] = 'jpeg';
            } elseif ($format === 'avif') {
                // AVIF-spezifische Einstellungen
                $baseOptions['format'] = 'avif';
                $baseOptions['quality'] = 80; // AVIF kann niedrigere Quality bei gleicher visueller Qualität
            } else {
                $baseOptions['format'] = $format;
            }
        }

        $options->setImagineOptions($baseOptions);
        return $options;
    }

    private static function getRetinaConfig(ResizeConfiguration $baseConfig, int $width, int $originalWidth, int $factor = 2): ?ResizeConfiguration
    {
        if ($width * $factor <= $originalWidth) {
            $retinaConfig = clone $baseConfig;
            $retinaConfig->setWidth((int)($width * $factor));
            
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
        // Leere Sources abfangen
        if (empty($src)) {
            return '';
        }

        // Nur vollständige Bildpfade verwenden (AVIF hinzugefügt)
        $srcsetParts = [];

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif|heic)$/i', $src)) {
            $srcsetParts[] = $src . ' 1x';
        }

        if ($retinaSrc && preg_match('/\.(jpg|jpeg|png|gif|webp|avif|heic)$/i', $retinaSrc)) {
            $srcsetParts[] = $retinaSrc . ' 2x';
        }

        if ($retina3xSrc && preg_match('/\.(jpg|jpeg|png|gif|webp|avif|heic)$/i', $retina3xSrc)) {
            $srcsetParts[] = $retina3xSrc . ' 3x';
        }

        $srcset = implode(', ', $srcsetParts);

        $result = empty($srcset) ? '' : sprintf(
            '<source type="%s" data-srcset="%s"%s>',
            $type,
            self::cleanAttribute($srcset),
            $mediaQuery ? ' media="' . $mediaQuery . '"' : ''
        );

        // Leere Results prüfen
        if (empty($result)) {
            return '';
        }

        return $result;
    }

    private static function cleanAttribute($str): string
    {
        if (empty($str) || is_int($str) || is_bool($str)) {
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
        // Cache-Schlüssel erstellen
        $cacheKey = md5($path . '_' . serialize($config) . '_' . serialize($options->getImagineOptions()));

        // Aus Cache laden, wenn vorhanden
        if (isset(self::$processedImagesCache[$cacheKey])) {
            return self::$processedImagesCache[$cacheKey];
        }

        $container = self::getContainer();
        $imageFactory = $container->get('contao.image.factory');
        $rootDir = $container->getParameter('kernel.project_dir');

        $processedImage = $imageFactory->create($path, $config, $options);
        $processedPath = $processedImage->getPath();

        // Vollständigen relativen Pfad erstellen
        $relativePath = str_replace($rootDir, '', $processedPath);

        // Sicherstellen, dass der Pfad mit .jpg, .png etc. endet
        if (!preg_match('/\.(jpg|jpeg|png|gif|webp|avif|heic)$/i', $relativePath)) {
            $result = ['path' => $processedPath, 'src' => ''];
            self::$processedImagesCache[$cacheKey] = $result;
            return $result;
        }

        // URL-Encode jedes Verzeichnis-Segment einzeln
        $pathParts = explode('/', $relativePath);
        $encodedParts = array_map('rawurlencode', $pathParts);
        $encodedPath = implode('/', $encodedParts);

        $result = [
            'path' => $processedPath,
            'src' => $encodedPath
        ];

        // Im Cache speichern
        self::clearCacheIfNeeded();
        self::$processedImagesCache[$cacheKey] = $result;
        self::$processedImagesCacheSize++;

        return $result;
    }

    /**
     * Lädt Metadaten für ein Bild
     */
    private static function loadImageMetadata($imageSource, string $currentLanguage): array
    {
        if ($imageObject = FilesModel::findByUuid($imageSource)) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = [];

            if (is_array($imageMeta) && !empty($imageMeta)) {
                $currentMeta = isset($imageMeta[$currentLanguage]) ? $imageMeta[$currentLanguage] : reset($imageMeta);
                if (is_array($currentMeta)) {
                    foreach ($currentMeta as $key => $value) {
                        $meta[$key] = self::cleanAttribute($value);
                    }
                }
            }
            return ['meta' => $meta, 'path' => $imageObject->path];
        }
        
        return ['meta' => [], 'path' => $imageSource];
    }

    /**
     * Verarbeitet die Bildgröße-Konfiguration 
     */
    private static function processImageSize($size, int $originalWidth, int $originalHeight): array
    {
        $config = new ResizeConfiguration();
        $baseWidth = $originalWidth;
        $baseHeight = $originalHeight;
        
        if ($size) {
            if (is_string($size) && strpos($size, 'a:') === 0) {
                $size = StringUtil::deserialize($size);
            }

            if (is_array($size)) {
                $requestedWidth = !empty($size[0]) ? (int)$size[0] : null;
                $requestedHeight = !empty($size[1]) ? (int)$size[1] : null;
                $mode = !empty($size[2]) ? $size[2] : "proportional";

                if ($requestedWidth) {
                    $config->setWidth((int)$requestedWidth);
                }
                if ($requestedHeight) {
                    $config->setHeight((int)$requestedHeight);
                }
                if ($mode) {
                    $config->setMode($mode);
                }

                $baseWidth = $requestedWidth ?? ($requestedHeight ? (int)round($originalWidth * ($requestedHeight / $originalHeight)) : $originalWidth);
                $baseHeight = $requestedHeight ?? ($requestedWidth ? (int)round($originalHeight * ($requestedWidth / $originalWidth)) : $originalHeight);
            } else {
                $config->setMode("proportional");
            }
        }
        
        return ['config' => $config, 'width' => $baseWidth, 'height' => $baseHeight];
    }

    /**
     * Löst eine Bildgrößen-ID oder Config-Key zu den tatsächlichen Werten auf
     */
    private static function resolveSizeConfiguration($size): ?array
    {
        if (!is_array($size) || count($size) < 3) {
            return $size; // Nicht das erwartete Format, unverändert zurückgeben
        }

        $thirdElement = $size[2];
        
        // Cache-Check für bessere Performance
        $cacheKey = 'size_' . $thirdElement;
        if (isset(self::$sizeConfigCache[$cacheKey])) {
            return self::$sizeConfigCache[$cacheKey];
        }
        
        // Prüfen ob es eine numerische ID ist (gespeicherte Bildgröße)
        if (is_numeric($thirdElement) && (int)$thirdElement > 0) {
            try {
                $container = self::getContainer();
                $connection = $container->get('database_connection');
                
                // Bildgröße aus der Datenbank laden
                $imageSizeConfig = $connection->fetchAssociative('SELECT * FROM tl_image_size WHERE id = ?', [(int)$thirdElement]);
                
                if ($imageSizeConfig) {
                    $result = [
                        (int)$imageSizeConfig['width'] ?: '',
                        (int)$imageSizeConfig['height'] ?: '',
                        $imageSizeConfig['resizeMode'] ?: 'proportional'
                    ];
                    self::$sizeConfigCache[$cacheKey] = $result;
                    return $result;
                }
            } catch (\Exception $e) {
                $logger = self::getContainer()->get('monolog.logger.contao');
                $logger->error('Fehler beim Laden der Bildgröße mit ID ' . $thirdElement . ': ' . $e->getMessage());
            }
        }
        
        // Prüfen ob es ein Config-Key ist (beginnt mit _)
        if (is_string($thirdElement) && strpos($thirdElement, '_') === 0) {
            try {
                $container = self::getContainer();
                $configKey = substr($thirdElement, 1); // Underscore entfernen
                
                // Versuche die Konfiguration direkt aus dem Parameter zu laden
                $imageSizeConfigs = $container->getParameter('contao.image.sizes');
                
                if (isset($imageSizeConfigs[$configKey])) {
                    $config = $imageSizeConfigs[$configKey];
                    $result = [
                        isset($config['width']) ? (int)$config['width'] : '',
                        isset($config['height']) ? (int)$config['height'] : '',
                        isset($config['resize_mode']) ? $config['resize_mode'] : 'proportional'
                    ];
                    self::$sizeConfigCache[$cacheKey] = $result;
                    return $result;
                }
            } catch (\Exception $e) {
                $logger = self::getContainer()->get('monolog.logger.contao');
                $logger->error('Fehler beim Laden der Bildgröße mit Key ' . $thirdElement . ': ' . $e->getMessage());
            }
        }
        
        // Fallback: Original-Array zurückgeben
        return $size;
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

        $container = self::getContainer();
        $rootDir = $container->getParameter('kernel.project_dir');
        $imageFactory = $container->get('contao.image.factory');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? $container->getParameter('kernel.default_locale');
        $originalWidth = $originalHeight = 0;

        // Metadaten verarbeiten
        $imageData = self::loadImageMetadata($imageSource, $currentLanguage);
        $meta = $imageData['meta'];
        $relativeImagePath = $imageData['path'];

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
            case 'avif':
                // AVIF-Dateien verarbeiten
                try {
                    if (!($imageInfo = @getimagesize($baseImagePath)) || !is_array($imageInfo)) {
                        $converted = self::convertToJpeg($baseImagePath);
                        if ($converted) {
                            $isAvif = false;
                            $baseImagePath = $converted['path'];
                            $absoluteImagePath = $converted['path'];
                        } else {
                            return self::handleUnknownFormat($baseImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy);
                        }
                    } else {
                        $isAvif = true;
                        $baseImagePath = $imageFormat['path'];
                        $absoluteImagePath = $imageFormat['path'];
                    }
                } catch (\Exception $e) {
                    $logger = self::getContainer()->get('monolog.logger.contao');
                    $logger->notice('Fehlerhaftes AVIF-Bild gefunden: ' . $baseImagePath . ' - ' . $e->getMessage());

                    $converted = self::convertToJpeg($baseImagePath);
                    if ($converted) {
                        $isAvif = false;
                        $baseImagePath = $converted['path'];
                        $absoluteImagePath = $converted['path'];
                    } else {
                        return self::handleUnknownFormat($baseImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy);
                    }
                }
                break;
            case 'webp':
                // WebP-Dateien speziell verarbeiten
                try {
                    if (!($imageInfo = @getimagesize($baseImagePath)) || !is_array($imageInfo)) {
                        $converted = self::convertToJpeg($baseImagePath);
                        if ($converted) {
                            $isWebp = false;
                            $baseImagePath = $converted['path'];
                            $absoluteImagePath = $converted['path'];
                        } else {
                            return self::handleUnknownFormat($baseImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy);
                        }
                    } else {
                        $isWebp = true;
                        $baseImagePath = $imageFormat['path'];
                        $absoluteImagePath = $imageFormat['path'];
                    }
                } catch (\Exception $e) {
                    $logger = self::getContainer()->get('monolog.logger.contao');
                    $logger->notice('Fehlerhaftes WebP-Bild gefunden: ' . $baseImagePath . ' - ' . $e->getMessage());

                    $converted = self::convertToJpeg($baseImagePath);
                    if ($converted) {
                        $isWebp = false;
                        $baseImagePath = $converted['path'];
                        $absoluteImagePath = $converted['path'];
                    } else {
                        return self::handleUnknownFormat($baseImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy);
                    }
                }
                break;
            default:
                $isAvif = false;
                $isWebp = false;
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

        // Bildgröße auflösen und verarbeiten
        $size = self::resolveSizeConfiguration($size);
        $sizeData = self::processImageSize($size, $originalWidth, $originalHeight);
        $config = $sizeData['config'];
        $baseWidth = $sizeData['width'];
        $baseHeight = $sizeData['height'];

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
                $config->setHeight($height);
                $config->setMode($mode);
            } else {
                $config->setWidth((int)$width);
                if ($mode !== "") {
                    $config->setMode($mode);
                }
            }

            try {

                // Sources für alle Media Queries generieren
                if ($breakpoint['maxWidth']) {
                    $mediaQuery = "(max-width: {$breakpoint['maxWidth']}px)";
                    $has3x = $width <= 768 && $width * 3 <= $originalWidth;
                    $has2x = $width * 2 <= $originalWidth;

                    // AVIF Source (höchste Priorität) - immer versuchen zu konvertieren
                    try {
                        $tempAvifSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('avif'))['src']);
                        $tempAvif2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('avif'))['src']) : null;
                        $tempAvif3x = $has3x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 3), self::getResizeOptions('avif'))['src']) : null;
                        
                        $sources[] = self::generateSource("image/avif", $tempAvifSrc, $tempAvif2x, $tempAvif3x, $mediaQuery);
                    } catch (\Exception $e) {
                        // AVIF fehlgeschlagen - stillschweigendes Fallback
                    }

                    // WebP Source - je nach Originalformat
                    if (!$isWebp) {
                        // Nur wenn Original NICHT WebP ist
                        try {
                            $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('webp'))['src']);
                            $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('webp'))['src']) : null;
                            $tempWebp3x = $has3x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 3), self::getResizeOptions('webp'))['src']) : null;
                            
                            $sources[] = self::generateSource("image/webp", $tempWebpSrc, $tempWebp2x, $tempWebp3x, $mediaQuery);
                        } catch (\Exception $e) {
                            // WebP fehlgeschlagen
                        }
                    } else {
                        // Original ist bereits WebP - direkt verwenden
                        $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions())['src']);
                        $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions())['src']) : null;
                        $tempWebp3x = $has3x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 3), self::getResizeOptions())['src']) : null;
                        
                        $sources[] = self::generateSource("image/webp", $tempWebpSrc, $tempWebp2x, $tempWebp3x, $mediaQuery);
                    }

                    // JPEG Source entfernt - WebP hat 97%+ Browser-Support in 2025
                    
                } elseif (!$breakpoint['maxWidth']) {
                    // Fallback ohne Media Query
                    $has2x = $width * 2 <= $originalWidth;

                    // AVIF Source (Fallback ohne Media Query)
                    try {
                        $tempAvifSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('avif'))['src']);
                        $tempAvif2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('avif'))['src']) : null;
                        
                        $sources[] = self::generateSource("image/avif", $tempAvifSrc, $tempAvif2x);
                    } catch (\Exception $e) {
                        // AVIF fehlgeschlagen - stillschweigendes Fallback
                    }

                    // WebP Source (Universal Fallback da 97%+ Unterstützung)
                    if (!$isWebp) {
                        try {
                            $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('webp'))['src']);
                            $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('webp'))['src']) : null;
                            
                            $sources[] = self::generateSource("image/webp", $tempWebpSrc, $tempWebp2x);
                        } catch (\Exception $e) {
                            // WebP fehlgeschlagen
                        }
                    } else {
                        // Original ist bereits WebP
                        $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions())['src']);
                        $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions())['src']) : null;
                        
                        $sources[] = self::generateSource("image/webp", $tempWebpSrc, $tempWebp2x);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }
        
        // Standard-Image für Lightbox und Fallbacks generieren
        $standardConfig = new ResizeConfiguration();
        $standardConfig->setWidth((int)$baseWidth);
        if ($baseHeight) {
            $standardConfig->setHeight((int)$baseHeight);
        }
        if (!empty($size[2])) {
            $standardConfig->setMode($size[2]);
        }
        
        $standardImage = self::processImage($absoluteImagePath, $standardConfig, self::getResizeOptions());
        $standardSrc = self::encodePath($standardImage['src']);
        
        // Lightbox Image
        $lightboxImageSrc = $standardSrc;
        if ($colorBox && ($originalWidth > 1200 || $originalHeight > 1200)) {
            $lightboxConfig = new ResizeConfiguration();
            $lightboxConfig->setWidth(1200)->setHeight(1200)->setMode('box');
            try {
                // Für Lightbox das beste verfügbare Format verwenden
                $lightboxFormat = null;
                if (isset($isAvif) && $isAvif) {
                    $lightboxFormat = 'avif';
                } elseif (isset($isWebp) && $isWebp) {
                    $lightboxFormat = 'webp';
                }
                
                $lightboxImage = self::processImage(
                    $absoluteImagePath,
                    $lightboxConfig,
                    self::getResizeOptions($lightboxFormat)
                );
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
            '<img %s data-src="%s" alt="%s"%s loading="lazy">',
            $classAttribute,
            self::cleanAttribute($standardSrc),
            $alt,
            $title ? ' title="' . $title . '"' : ''
        );
        $imgTag .= '</picture>';

        // Figure Tag erstellen
        $finalOutput = '<figure>' . $imgTag;


        if ($finalCaption) {
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
            // Prüfe auf vollständigen Bildpfad und korrektes Format (inkl. AVIF)
            return preg_match('/\.(jpg|jpeg|png|gif|webp|avif|heic)\s+\d+w$/i', $entry);
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

        $rootDir = self::getContainer()->getParameter('kernel.project_dir');
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

        // Bildgröße auflösen falls ID übergeben wurde
        $size = self::resolveSizeConfiguration($size);

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

                if ($width) $config->setWidth((int)$width);
                if ($height) $config->setHeight((int)$height);
                if ($mode) $config->setMode($mode);
            } else {
                // Bei leeren Werten setzen wir nur den proportionalen Modus
                $config->setMode("proportional");
            }
        }

        try {
            $processedImage = self::getContainer()
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

    public static function getSvgCode($source, $alt = '', $size = null, $classes = ''): string
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');
        $fileModel = null;
        $fullPath = null;
        $relativePath = null; // Relative path for FilesModel lookup
        $meta = [];
        $finalLink = '';
        $finalTitle = '';

        // Prüfen, ob $source eine UUID ist und FilesModel laden
        if (Uuid::isValid($source)) {
            $fileModel = FilesModel::findByUuid($source);
            if ($fileModel) {
                $relativePath = $fileModel->path;
                $fullPath = $projectDir . '/' . urldecode($relativePath);
            } else {
                return ''; // UUID ist gültig, aber kein FileModel gefunden
            }
        } // Prüfen, ob $source ein gültiger Pfad ist
        elseif (is_string($source) && strpos($source, '/') !== false) {
            $relativePath = ltrim(urldecode($source), '/'); // Store relative path
            $testPath = $projectDir . '/' . $relativePath;
            if (file_exists($testPath) && strtolower(pathinfo($testPath, PATHINFO_EXTENSION)) === 'svg') {
                $fullPath = $testPath;
                // Versuche, das FilesModel anhand des relativen Pfads zu finden
                $fileModel = FilesModel::findByPath($relativePath);
            } else {
                return ''; // Pfad ungültig oder Datei nicht gefunden/kein SVG
            }
        } else {
            return ''; // Weder gültige UUID noch gültiger Pfad
        }

        // Wenn kein fullPath gefunden wurde (sollte nicht passieren, aber sicher ist sicher)
        if (!$fullPath || !file_exists($fullPath)) {
            return '';
        }


        // Metadaten laden, wenn ein FilesModel gefunden wurde
        if ($fileModel) {
            $imageMeta = StringUtil::deserialize($fileModel->meta, true);
            $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? self::getContainer()->getParameter('kernel.default_locale');

            if (is_array($imageMeta) && !empty($imageMeta)) {
                $currentMeta = isset($imageMeta[$currentLanguage]) ? $imageMeta[$currentLanguage] : reset($imageMeta);
                if (is_array($currentMeta)) {
                    foreach ($currentMeta as $key => $value) {
                        // Leere Metadaten-Werte überspringen
                        if (!empty($value)) {
                            $meta[$key] = self::cleanAttribute((string)$value);
                        }
                    }
                }
            }
            $finalLink = !empty($meta['link']) ? $meta['link'] : '';
            $finalTitle = !empty($meta['title']) ? $meta['title'] : '';
        }


        $svgContent = file_get_contents($fullPath);
        if (!$svgContent) {
            return '';
        }

        // Alt-Text bestimmen: Priorität hat der Parameter, dann Metadaten
        $finalAlt = self::cleanAttribute($alt ?: (!empty($meta['alt']) ? $meta['alt'] : ''));
        // Titel bestimmen: Priorität hat Metadaten-Titel, dann $finalAlt
        $svgTitle = self::cleanAttribute($finalTitle ?: $finalAlt);


        // Style basierend auf Größe
        $style = '';
        if (is_array($size)) {
            if (!empty($size[0])) {
                $style .= 'width: ' . (int)$size[0] . 'px;';
            }
            if (isset($size[1]) && !empty($size[1])) { // Prüfe explizit, ob Index 1 existiert
                $style .= ' height: ' . (int)$size[1] . 'px;';
            }
        }

        // Basis-Klassen für SVG
        $baseClasses = trim('svg-image ' . self::cleanAttribute($classes));

        // SVG modifizieren
        // Entferne vorhandene class und style Attribute im svg tag, behalte aber andere Attribute
        $svgContent = preg_replace('/<svg([^>]*) (class|style)="[^"]*"/i', '<svg$1', $svgContent);
        // Füge neue class und style Attribute hinzu
        $svgContent = preg_replace('/<svg /i', '<svg class="' . $baseClasses . '"' . ($style ? ' style="' . $style . '"' : '') . ' ', $svgContent, 1);


        // Entferne eventuell vorhandene fill-Attribute, um CSS-Steuerung zu ermöglichen (Optional)
        // $svgContent = preg_replace('/ fill="[^"]*"/', '', $svgContent);

        // Füge title für Barrierefreiheit hinzu oder ersetze bestehenden
        if ($svgTitle) {
            $titleId = 'svg-title-' . bin2hex(random_bytes(4)); // Eindeutige ID generieren
            if (preg_match('/<title[^>]*>.*?<\/title>/i', $svgContent)) {
                // Ersetze existierenden title und dessen ID, falls vorhanden
                $svgContent = preg_replace('/<title[^>]*>(.*?)<\/title>/i', '<title id="' . $titleId . '">' . htmlspecialchars($svgTitle) . '</title>', $svgContent, 1);
            } else {
                // Füge title hinzu, wenn keiner existiert
                $svgContent = preg_replace('/(<svg[^>]*>)/i', '$1<title id="' . $titleId . '">' . htmlspecialchars($svgTitle) . '</title>', $svgContent, 1);
            }
            // Füge aria-labelledby hinzu oder aktualisiere es
            if (strpos($svgContent, 'aria-labelledby=') !== false) {
                $svgContent = preg_replace('/aria-labelledby="[^"]*"/', 'aria-labelledby="' . $titleId . '"', $svgContent, 1);
            } else {
                $svgContent = preg_replace('/<svg /i', '<svg aria-labelledby="' . $titleId . '" ', $svgContent, 1);
            }
        } else {
            // Wenn kein Titel vorhanden ist, entferne aria-labelledby
            $svgContent = preg_replace('/ aria-labelledby="[^"]*"/', '', $svgContent);
            // Und füge aria-label hinzu, falls ein finalAlt existiert
            if ($finalAlt && strpos($svgContent, 'aria-label=') === false) {
                $svgContent = preg_replace('/<svg /i', '<svg aria-label="' . htmlspecialchars($finalAlt) . '" ', $svgContent, 1);
            }
        }

        // Füge role="img" hinzu, wenn nicht vorhanden
        if (strpos($svgContent, 'role=') === false) {
            $svgContent = preg_replace('/<svg /i', '<svg role="img" ', $svgContent, 1);
        }

        // Mit Link umschließen, wenn vorhanden
        if ($finalLink) {
            $linkTitleAttr = $finalTitle ? ' title="' . htmlspecialchars($finalTitle) . '"' : ($finalAlt ? ' title="' . htmlspecialchars($finalAlt) . '"' : '');
            // Prüfen, ob der Link extern ist, um target="_blank" hinzuzufügen
            $targetBlank = (str_starts_with($finalLink, 'http://') || str_starts_with($finalLink, 'https://')) && !str_contains($finalLink, $_SERVER['HTTP_HOST'] ?? '');
            $targetAttr = $targetBlank ? ' target="_blank"' : '';
            $relAttr = $targetBlank ? ' rel="noopener noreferrer"' : ''; // Sicherheit für target="_blank"

            $svgContent = '<a href="' . htmlspecialchars($finalLink) . '"' . $linkTitleAttr . $targetAttr . $relAttr . '>' . $svgContent . '</a>';
        }

        return $svgContent;
    }
}
