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
use Vsm\VsmHelperTools\Helper\SchemaOrgHelper;

class ImageHelper
{
    private const DEFAULT_QUALITY = 85;
    private const LARGE_IMAGE_QUALITY = 75; // Reduzierte Qualität für große Bilder (>1600px)
    private const RETINA_QUALITY = 70; // Noch geringere Qualität für Retina-Varianten großer Bilder
    private const PNG_COMPRESSION = 6;
    private const LARGE_IMAGE_THRESHOLD = 1600; // Schwellenwert für "große" Bilder
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

    /**
     * Bestimmt die optimale Qualität basierend auf der Bildbreite und dem Retina-Faktor
     */
    private static function getOptimalQuality(int $width, int $retinaFactor = 1): int
    {
        // Berechne die tatsächliche Ausgabebreite
        $actualWidth = $width * $retinaFactor;
        
        // Für sehr große Bilder (über Threshold) reduzierte Qualität verwenden
        if ($actualWidth > self::LARGE_IMAGE_THRESHOLD) {
            // Bei Retina-Varianten noch weiter reduzieren
            if ($retinaFactor >= 2) {
                return self::RETINA_QUALITY;
            }
            return self::LARGE_IMAGE_QUALITY;
        }
        
        // Für normale Größen die Standard-Qualität
        return self::DEFAULT_QUALITY;
    }

    private static function handleImageFormat(string $imagePath): array
    {
        // Validierung: Nur wirklich korrupte Pfade abfangen
        if (!mb_check_encoding($imagePath, 'UTF-8')) {
            return ['type' => 'unknown', 'path' => $imagePath];
        }
        
        // Prüfe nur auf problematische Kontrollzeichen
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $imagePath)) {
            return ['type' => 'unknown', 'path' => $imagePath];
        }

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

    private static function handleUnknownFormat($imagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy, $colorBox = false, $imageUrl = ''): string
    {
        // SVG direkt ausgeben - mit korrekten Parametern
        if (strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)) === 'svg') {
            return self::handleSvg($imagePath, $rootDir, $altText, $meta, $headline, [], $class, $colorBox, $imageUrl, $caption);
        }

        $imageSrc = str_replace($rootDir, '', $imagePath);
        $imageSrc = self::encodePath($imageSrc);

        $metaAlt = (isset($meta['alt']) && !empty(trim($meta['alt']))) ? trim($meta['alt']) : null;
        $metaTitle = (isset($meta['title']) && !empty(trim($meta['title']))) ? trim($meta['title']) : null;
        $metaCaption = (isset($meta['caption']) && !empty(trim($meta['caption']))) ? trim($meta['caption']) : null;
        $metaLink = (isset($meta['link']) && !empty(trim($meta['link']))) ? trim($meta['link']) : null;
        
        $alt = self::cleanAttribute($metaAlt ?: $altText ?: $headline ?: '');
        $title = self::cleanAttribute($metaTitle ?: $headline ?: $caption ?: '');
        $finalCaption = self::cleanAttribute($metaCaption ?: $caption ?: '');
        $finalLink = self::cleanAttribute($metaLink ?: $imageUrl ?: '');

        // PageSpeed-Optimierung: Bildabmessungen ermitteln wenn möglich
        $dimensionAttributes = '';
        if (file_exists($imagePath) && ($imageInfo = @getimagesize($imagePath)) && is_array($imageInfo)) {
            $width = (int)$imageInfo[0];
            $height = (int)$imageInfo[1];
            if ($width > 0 && $height > 0) {
                $dimensionAttributes = sprintf(' width="%d" height="%d"', $width, $height);
            }
        }

        // Picture Tag mit Original-Bild
        $imgTag = '<picture>';
        $imgTag .= sprintf(
            '<img %ssrc="%s" alt="%s"%s%s%s%s>',
            $lazy ? 'data-' : '',
            $imageSrc,
            $alt,
            $class ? ' class="' . ($lazy ? 'lazy ' : '') . htmlspecialchars($class) . '"' : ($lazy ? ' class="lazy"' : ''),
            $title ? ' title="' . htmlspecialchars($title) . '"' : '',
            $dimensionAttributes,
            $lazy ? ' loading="lazy"' : ''
        );
        $imgTag .= '</picture>';

        $finalOutput = '<figure>' . $imgTag;
        if ($finalCaption) {
            $finalOutput .= '<figcaption>' . $finalCaption . '</figcaption>';
        }
        $finalOutput .= '</figure>';

        // Link/Lightbox Handling für unbekannte Formate
        if ($finalLink || $colorBox) {
            // Für unbekannte Formate: Original-Datei für Lightbox verwenden
            $lightboxSrc = $colorBox ? $imageSrc : $finalLink;
            
            $linkAttributes = [
                'href' => self::cleanAttribute($lightboxSrc),
                'title' => $title ?: null,
                'target' => $finalLink && !$colorBox ? '_blank' : null,
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

        return $finalOutput;
    }

    private static function convertToJpeg(string $imagePath): ?array
    {
        try {
            // Validierung: Nur wirklich korrupte Pfade abfangen
            if (!mb_check_encoding($imagePath, 'UTF-8')) {
                return null;
            }
            
            // Prüfe nur auf problematische Kontrollzeichen
            if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $imagePath)) {
                return null;
            }

            $container = self::getContainer();
            $projectDir = $container->getParameter('kernel.project_dir');
            $logger = $container->get('monolog.logger.contao');

            // Existenz-Check ohne Logging - wird bereits vorher geprüft
            if (!file_exists($imagePath)) {
                return null;
            }

            // Prüfen ob es überhaupt ein unterstütztes Bildformat ist
            $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'tiff', 'tif'];
            
            if (!in_array($extension, $supportedFormats)) {
                $logger->debug('Bildformat nicht für Konvertierung unterstützt: ' . $extension);
                return null;
            }

            // Prüfen ob ImageMagick verfügbar ist
            if (!extension_loaded('imagick')) {
                $logger->debug('ImageMagick nicht verfügbar für Konvertierung');
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

            try {
                $image = new \Imagick();
                $image->readImage($imagePath);
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(self::DEFAULT_QUALITY);
                $image->writeImage($targetPath);
                $image->clear();
                $image->destroy();

                if (file_exists($targetPath)) {
                    $logger->info('Successfully converted ' . $extension . ' to JPEG: ' . $targetPath);
                    return [
                        'path' => $targetPath,
                        'src' => str_replace($projectDir, '', $targetPath)
                    ];
                }
            } catch (\ImagickException $e) {
                $logger->debug('Conversion failed for ' . $imagePath . ': ' . $e->getMessage());
                return null;
            }

            return null;

        } catch (\Exception $e) {
            if (isset($logger)) {
                $logger->debug(
                    'Image conversion exception: ' . $e->getMessage(),
                    ['file' => $imagePath]
                );
            }
            return null;
        }
    }

    private static function getResizeOptions($format = null, int $width = 0, int $retinaFactor = 1): ResizeOptions
    {
        $options = new ResizeOptions();
        
        // Dynamische Qualität basierend auf Bildgröße bestimmen
        $quality = $width > 0 ? self::getOptimalQuality($width, $retinaFactor) : self::DEFAULT_QUALITY;
        
        $baseOptions = [
            'quality' => $quality,
            'png_compression_level' => self::PNG_COMPRESSION
        ];

        if ($format) {
            // Explizite Format-Angabe für korrekte Konvertierung
            if ($format === 'jpeg' || $format === 'jpg') {
                $baseOptions['format'] = 'jpeg';
            } elseif ($format === 'avif') {
                // AVIF-spezifische Einstellungen - aber mit angepasster Qualität
                $baseOptions['format'] = 'avif';
                // AVIF ist effizienter, daher können wir eine etwas höhere Qualität verwenden
                $baseOptions['quality'] = min(95, $quality + 10);
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
        // Null, leere Werte, int, bool und float direkt behandeln
        if (empty($str) || is_int($str) || is_bool($str) || is_float($str)) {
            return '';
        }

        // Numerische Strings in String umwandeln
        if (is_numeric($str)) {
            $str = (string)$str;
        }

        // Sicherstellen, dass es ein String ist
        if (!is_string($str)) {
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
        $imageObject = null;
        

        
        // Zuerst versuchen als String-UUID
        if (is_string($imageSource) && class_exists('Symfony\Component\Uid\Uuid') && \Symfony\Component\Uid\Uuid::isValid($imageSource)) {
            $imageObject = FilesModel::findByUuid($imageSource);
        }
        
        // Wenn das fehlschlägt, prüfen ob es eine binäre UUID ist (Contao speichert UUIDs als 16-Byte binary)
        if (!$imageObject && is_string($imageSource) && strlen($imageSource) === 16) {
            // Könnte eine binäre UUID sein - direkt mit FilesModel versuchen
            $imageObject = FilesModel::findByUuid($imageSource);
        }
        
        // Falls keine UUID oder kein FilesModel gefunden, versuche als Pfad
        if (!$imageObject && is_string($imageSource)) {
            // Pfad normalisieren (führenden Slash entfernen falls vorhanden)
            $normalizedPath = ltrim($imageSource, '/');
            $imageObject = FilesModel::findByPath($normalizedPath);
        }

        if ($imageObject) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = [];

            if (is_array($imageMeta) && !empty($imageMeta)) {
                $currentMeta = isset($imageMeta[$currentLanguage]) ? $imageMeta[$currentLanguage] : reset($imageMeta);
                if (is_array($currentMeta)) {
                    foreach ($currentMeta as $key => $value) {
                        // Rohe Werte speichern, nicht hier bereits cleanen
                        if (!empty($value) && is_string($value)) {
                            $meta[$key] = trim($value);
                        }
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

                $baseWidth = $requestedWidth ?? ($requestedHeight && $originalHeight > 0 ? (int)round($originalWidth * ($requestedHeight / $originalHeight)) : $originalWidth);
                $baseHeight = $requestedHeight ?? ($requestedWidth && $originalWidth > 0 ? (int)round($originalHeight * ($requestedWidth / $originalWidth)) : $originalHeight);
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
        // Null oder leere Werte zurückgeben
        if (empty($size)) {
            return $size;
        }

        // Serialisierte Strings deserialisieren
        if (is_string($size) && strpos($size, 'a:') === 0) {
            $size = StringUtil::deserialize($size);
        }

        // Einzelne numerische ID (Bildgröße aus Datenbank)
        if (is_numeric($size) && (int)$size > 0) {
            $cacheKey = 'size_id_' . $size;
            if (isset(self::$sizeConfigCache[$cacheKey])) {
                return self::$sizeConfigCache[$cacheKey];
            }

            try {
                $container = self::getContainer();
                $connection = $container->get('database_connection');

                // Bildgröße aus der Datenbank laden
                $imageSizeConfig = $connection->fetchAssociative('SELECT * FROM tl_image_size WHERE id = ?', [(int)$size]);

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
                $logger->error('Fehler beim Laden der Bildgröße mit ID ' . $size . ': ' . $e->getMessage());
            }
        }

        // String mit underscore (Config-Key)
        if (is_string($size) && strpos($size, '_') === 0) {
            $cacheKey = 'size_key_' . $size;
            if (isset(self::$sizeConfigCache[$cacheKey])) {
                return self::$sizeConfigCache[$cacheKey];
            }

            try {
                $container = self::getContainer();
                $configKey = substr($size, 1); // Underscore entfernen

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
                $logger->error('Fehler beim Laden der Bildgröße mit Key ' . $size . ': ' . $e->getMessage());
            }
        }

        // Array-Verarbeitung
        if (is_array($size)) {
            // Array mit mindestens 3 Elementen (dritter Wert könnte ID oder Key sein)
            if (count($size) >= 3) {
                $thirdElement = $size[2];

                // Cache-Check für bessere Performance
                $cacheKey = 'size_array_' . $thirdElement;
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
            }

            // Array bereits in richtigem Format oder weniger als 3 Elemente - direkt zurückgeben
            return $size;
        }

        // Fallback: Original-Wert zurückgeben
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
        ?string $imageUrl = '',
        bool $includeSchema = true // Standardmäßig aktiviert für bessere SEO
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

        $absoluteImagePath = $rootDir . '/' . ltrim($relativeImagePath, '/');

        // Pfad für Existenz-Check korrekt auflösen
        if (!file_exists($absoluteImagePath)) {
            return '';
        }

        // Bildformat-Prüfung
        $imageFormat = self::handleImageFormat($absoluteImagePath);

        switch ($imageFormat['type']) {
            case 'svg':
                return self::handleSvg($absoluteImagePath, $rootDir, $altText, $meta, $headline, $size, $class, $colorBox, $imageUrl, $caption);
            case 'unknown':
                return self::handleUnknownFormat($absoluteImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy, $colorBox, $imageUrl);
            case 'avif':
                // AVIF-Dateien verarbeiten - nur prüfen, ob getimagesize funktioniert
                if (!($imageInfo = @getimagesize($absoluteImagePath)) || !is_array($imageInfo)) {
                    // Wenn getimagesize fehlschlägt, als unknown behandeln
                    return self::handleUnknownFormat($absoluteImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy);
                }
                $isAvif = true;
                break;
            case 'webp':
                // WebP-Dateien verarbeiten - nur prüfen, ob getimagesize funktioniert
                if (!($imageInfo = @getimagesize($absoluteImagePath)) || !is_array($imageInfo)) {
                    // Wenn getimagesize fehlschlägt, als unknown behandeln
                    return self::handleUnknownFormat($absoluteImagePath, $rootDir, $altText, $meta, $headline, $class, $caption, $lazy);
                }
                $isWebp = true;
                break;
            default:
                $isAvif = false;
                $isWebp = false;
        }

        // Originalbild-Dimensionen prüfen
        if (!($originalImageInfo = @getimagesize($absoluteImagePath)) || !is_array($originalImageInfo)) {
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
                self::getResizeOptions(null, $baseWidth, 1)
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
            // Validierung der Breakpoint-Daten
            if (!isset($breakpoint['width']) || !is_numeric($breakpoint['width']) || (int)$breakpoint['width'] <= 0) {
                continue;
            }
            
            $config = new ResizeConfiguration();
            $width = (int)$breakpoint['width'];
            $mode = $size[2] ?? "proportional";

            if ($width > $baseWidth) {
                continue;
            }

            // Konfiguration für Crop-Mode
            if ($mode === 'crop' && !empty($size[0]) && !empty($size[1]) && is_numeric($size[0]) && is_numeric($size[1]) && (float)$size[0] > 0) {
                $ratio = (float)$size[1] / (float)$size[0];
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
                    
                    // Optimierung: Keine Retina-Varianten wenn diese zu groß wären
                    $has3x = ($width * 3) <= 1920 && $width <= 768 && $width * 3 <= $originalWidth;
                    $has2x = ($width * 2) <= 2048 && $width * 2 <= $originalWidth;

                    // AVIF Source (höchste Priorität) - immer versuchen zu konvertieren
                    try {
                        $tempAvifSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('avif', $width, 1))['src']);
                        $tempAvif2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('avif', $width, 2))['src']) : null;
                        $tempAvif3x = $has3x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 3), self::getResizeOptions('avif', $width, 3))['src']) : null;

                        $sources[] = self::generateSource("image/avif", $tempAvifSrc, $tempAvif2x, $tempAvif3x, $mediaQuery);
                    } catch (\Exception $e) {
                        // AVIF fehlgeschlagen - stillschweigendes Fallback
                    }

                    // WebP Source - je nach Originalformat
                    if (!$isWebp) {
                        // Nur wenn Original NICHT WebP ist
                        try {
                            $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('webp', $width, 1))['src']);
                            $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('webp', $width, 2))['src']) : null;
                            $tempWebp3x = $has3x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 3), self::getResizeOptions('webp', $width, 3))['src']) : null;

                            $sources[] = self::generateSource("image/webp", $tempWebpSrc, $tempWebp2x, $tempWebp3x, $mediaQuery);
                        } catch (\Exception $e) {
                            // WebP fehlgeschlagen
                        }
                    } else {
                        // Original ist bereits WebP - direkt verwenden
                        $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions(null, $width, 1))['src']);
                        $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions(null, $width, 2))['src']) : null;
                        $tempWebp3x = $has3x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 3), self::getResizeOptions(null, $width, 3))['src']) : null;

                        $sources[] = self::generateSource("image/webp", $tempWebpSrc, $tempWebp2x, $tempWebp3x, $mediaQuery);
                    }

                    // JPEG Source entfernt - WebP hat 97%+ Browser-Support in 2025

                } elseif (!$breakpoint['maxWidth']) {
                    // Fallback ohne Media Query
                    // Optimierung: Keine Retina-Varianten wenn diese zu groß wären
                    $has2x = ($width * 2) <= 2048 && $width * 2 <= $originalWidth;

                    // AVIF Source (Fallback ohne Media Query)
                    try {
                        $tempAvifSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('avif', $width, 1))['src']);
                        $tempAvif2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('avif', $width, 2))['src']) : null;

                        $sources[] = self::generateSource("image/avif", $tempAvifSrc, $tempAvif2x);
                    } catch (\Exception $e) {
                        // AVIF fehlgeschlagen - stillschweigendes Fallback
                    }

                    // WebP Source (Universal Fallback da 97%+ Unterstützung)
                    if (!$isWebp) {
                        try {
                            $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions('webp', $width, 1))['src']);
                            $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions('webp', $width, 2))['src']) : null;

                            $sources[] = self::generateSource("image/webp", $tempWebpSrc, $tempWebp2x);
                        } catch (\Exception $e) {
                            // WebP fehlgeschlagen
                        }
                    } else {
                        // Original ist bereits WebP
                        $tempWebpSrc = self::encodePath(self::processImage($absoluteImagePath, $config, self::getResizeOptions(null, $width, 1))['src']);
                        $tempWebp2x = $has2x ? self::encodePath(self::processImage($absoluteImagePath, self::getRetinaConfig($config, $width, $originalWidth, 2), self::getResizeOptions(null, $width, 2))['src']) : null;

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

        $standardImage = self::processImage($absoluteImagePath, $standardConfig, self::getResizeOptions(null, $baseWidth, 1));
        $standardSrc = self::encodePath($standardImage['src']);

        // Lightbox Image - immer größer als normale Anzeige
        $lightboxImageSrc = $standardSrc;
        if ($colorBox) {
            $lightboxConfig = new ResizeConfiguration();
            
            // Lightbox-Größe bestimmen: Maximal 1920x1080, aber nicht größer als Original
            $lightboxMaxWidth = min(1920, $originalWidth);
            $lightboxMaxHeight = min(1080, $originalHeight);
            
            // Nur resizen wenn das Originalbild größer als die normale Anzeige ist
            // oder wenn eine sinnvolle Lightbox-Größe möglich ist
            if ($originalWidth > $baseWidth || $originalHeight > $baseHeight || 
                $lightboxMaxWidth > $baseWidth || $lightboxMaxHeight > $baseHeight) {
                
                $lightboxConfig->setWidth($lightboxMaxWidth)->setHeight($lightboxMaxHeight)->setMode('box');
                
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
                        self::getResizeOptions($lightboxFormat, $lightboxMaxWidth, 1)
                    );
                    $lightboxImageSrc = $lightboxImage['src'];
                } catch (\Exception $e) {
                    // Fallback: Original-Bild direkt verwenden
                    $lightboxImageSrc = self::encodePath(str_replace($rootDir, '', $absoluteImagePath));
                }
            } else {
                // Bei sehr kleinen Originalbildern: Original direkt verwenden
                $lightboxImageSrc = self::encodePath(str_replace($rootDir, '', $absoluteImagePath));
            }
        }

        // HTML Generierung
        $classAttribute = $inSlider
            ? ($class ? ' class="' . htmlspecialchars($class) . '"' : '')
            : ($class ? ' class="lazy ' . htmlspecialchars($class) . '"' : ' class="lazy"');

        // Attribute vorbereiten - Meta-Daten haben ABSOLUTE Priorität
        // Meta-Daten müssen nicht leer sein und als String vorliegen
        $metaAlt = (isset($meta['alt']) && !empty(trim($meta['alt']))) ? trim($meta['alt']) : null;
        $metaTitle = (isset($meta['title']) && !empty(trim($meta['title']))) ? trim($meta['title']) : null;
        $metaCaption = (isset($meta['caption']) && !empty(trim($meta['caption']))) ? trim($meta['caption']) : null;
        $metaLink = (isset($meta['link']) && !empty(trim($meta['link']))) ? trim($meta['link']) : null;

        $alt = self::cleanAttribute($metaAlt ?: $altText ?: $headline ?: $caption ?: '');
        $title = self::cleanAttribute($metaTitle ?: $headline ?: $caption ?: $alt ?: '');
        $finalCaption = self::cleanAttribute($metaCaption ?: $caption ?: '');
        $finalLink = self::cleanAttribute($metaLink ?: $imageUrl ?: '');

        // PageSpeed-Optimierung: width/height Attribute hinzufügen wenn beide vorhanden
        $dimensionAttributes = '';
        if ($baseWidth && $baseHeight && is_numeric($baseWidth) && is_numeric($baseHeight) && $baseWidth > 0 && $baseHeight > 0) {
            $dimensionAttributes = sprintf(' width="%d" height="%d"', (int)$baseWidth, (int)$baseHeight);
        }

        // Picture Tag zusammenbauen
        $imgTag = '<picture>' . implode("\n", $sources);

        $imgTag .= sprintf(
            '<img %s data-src="%s" alt="%s"%s%s loading="lazy">',
            $classAttribute,
            self::cleanAttribute($standardSrc),
            $alt,
            $title ? ' title="' . $title . '"' : '',
            $dimensionAttributes
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

        // Schema.org hinzufügen wenn aktiviert
        if ($includeSchema) {
            try {
                // Schema.org direkt hier generieren für bessere Integration
                // Vollständige URL für Schema.org generieren
                $request = self::getContainer()->get('request_stack')->getCurrentRequest();
                $baseUrl = '';
                if ($request) {
                    $baseUrl = $request->getSchemeAndHttpHost();
                }
                
                $fullImageUrl = $baseUrl . $standardSrc;
                
                $schemaData = [
                    '@context' => 'https://schema.org',
                    '@type' => 'ImageObject',
                    'contentUrl' => $fullImageUrl,
                    'url' => $fullImageUrl
                ];
                
                // Metadaten hinzufügen
                if (!empty($meta['title']) || !empty($alt)) {
                    $name = strip_tags($meta['title'] ?? $alt);
                    // Name sollte kurz sein (max. 60 Zeichen)
                    if (strlen($name) > 60) {
                        $name = substr($name, 0, 57) . '...';
                    }
                    $schemaData['name'] = $name;
                }
                
                if (!empty($meta['alt']) || !empty($alt)) {
                    $description = strip_tags($meta['alt'] ?? $alt);
                    // Description kann länger sein, aber max. 300 Zeichen
                    if (strlen($description) > 300) {
                        $description = substr($description, 0, 297) . '...';
                    }
                    $schemaData['description'] = $description;
                }
                
                if (!empty($finalCaption)) {
                    $schemaData['caption'] = strip_tags($finalCaption);
                }
                
                if (!empty($meta['photographer'])) {
                    $schemaData['creator'] = [
                        '@type' => 'Person',
                        'name' => $meta['photographer']
                    ];
                }
                
                if (!empty($meta['copyright'])) {
                    $schemaData['copyrightHolder'] = [
                        '@type' => 'Organization',
                        'name' => $meta['copyright']
                    ];
                }
                
                // Bildabmessungen
                if ($baseWidth && $baseHeight) {
                    $schemaData['width'] = [
                        '@type' => 'QuantitativeValue',
                        'value' => $baseWidth,
                        'unitCode' => 'PX'
                    ];
                    $schemaData['height'] = [
                        '@type' => 'QuantitativeValue',
                        'value' => $baseHeight,
                        'unitCode' => 'PX'
                    ];
                }
                
                // Repräsentatives Bild markieren
                $schemaData['representativeOfPage'] = true;
                
                $schemaJson = json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $schemaHtml = '<script type="application/ld+json">' . $schemaJson . '</script>';
                
                $finalOutput = $schemaHtml . "\n" . $finalOutput;
            } catch (\Exception $e) {
                // Schema.org fehlgeschlagen - kein Problem, Bild funktioniert trotzdem
            }
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

    private static function handleSvg($baseImagePath, $rootDir, $altText, $meta, $headline, $size, $class, $colorBox = false, $imageUrl = '', $caption = ''): string
    {
        // Verwende getSvgCode für direkte SVG-Ausgabe (inline SVG - kein Lazy Loading nötig)
        $relativePath = str_replace($rootDir . '/', '', $baseImagePath);
        $svgCode = self::getSvgCode($relativePath, $altText, $size, $class);
        
        if (empty($svgCode)) {
            // Fallback zu <img> wenn getSvgCode fehlschlägt (hier könnte Lazy Loading sinnvoll sein)
            $imageSrc = str_replace($rootDir, '', $baseImagePath);
            $imageSrc = self::encodePath($imageSrc);
            $metaAlt = (isset($meta['alt']) && !empty(trim($meta['alt']))) ? trim($meta['alt']) : null;
            $alt = self::cleanAttribute($metaAlt ?: $altText ?: $headline ?: 'SVG Bild');
            
            $style = '';
            if ($size && is_array($size)) {
                $width = isset($size[0]) && $size[0] !== '' ? (int)$size[0] : null;
                // Für SVG-Fallback nur Breite setzen, Höhe sich automatisch ergeben lassen
                if ($width) $style .= "width: {$width}px; ";
            }

            // Für SVG-Fallback: nur lazy loading wenn das SVG groß/komplex ist
            $svgCode = sprintf(
                '<img src="%s" alt="%s" class="%s"%s>',
                $imageSrc,
                $alt,
                htmlspecialchars($class),
                $style ? ' style="' . htmlspecialchars($style) . '"' : ''
            );
        }

        // Meta-Daten für Link und Caption verarbeiten
        $metaCaption = (isset($meta['caption']) && !empty(trim($meta['caption']))) ? trim($meta['caption']) : null;
        $metaLink = (isset($meta['link']) && !empty(trim($meta['link']))) ? trim($meta['link']) : null;
        $metaTitle = (isset($meta['title']) && !empty(trim($meta['title']))) ? trim($meta['title']) : null;
        
        $finalCaption = self::cleanAttribute($metaCaption ?: $caption ?: '');
        $finalLink = self::cleanAttribute($metaLink ?: $imageUrl ?: '');
        $title = self::cleanAttribute($metaTitle ?: $headline ?: $finalCaption ?: $altText ?: '');
        
        // Figure Tag erstellen
        $finalOutput = '<figure>' . $svgCode;
        
        if ($finalCaption) {
            $finalOutput .= '<figcaption>' . $finalCaption . '</figcaption>';
        }
        
        $finalOutput .= '</figure>';

        // Link/Lightbox Handling für SVG
        if ($finalLink || $colorBox) {
            // Für SVG Lightbox: Immer Original-SVG verwenden (da vektorbasiert und unendlich skalierbar)
            $lightboxSrc = $colorBox ? self::encodePath(str_replace($rootDir, '', $baseImagePath)) : $finalLink;
            
            $linkAttributes = [
                'href' => self::cleanAttribute($lightboxSrc),
                'title' => $title ?: null,
                'target' => $finalLink && !$colorBox ? '_blank' : null,
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

        return $finalOutput;
    }

    public static function generateImageURL($imageSource, array|string|null $size = null): string
    {
        $rootDir = self::getContainer()->getParameter('kernel.project_dir');
        $imageObject = null;
        $absolutePath = null;
        

        
        // Prüfen, ob $imageSource eine UUID ist (String oder binär)
        if ((is_string($imageSource) && Uuid::isValid($imageSource)) || 
            (is_string($imageSource) && strlen($imageSource) === 16)) {
            $imageObject = FilesModel::findByUuid($imageSource);
            if ($imageObject) {
                $absolutePath = $rootDir . '/' . urldecode($imageObject->path);
            } else {
                return ''; // UUID ist gültig, aber kein FileModel gefunden
            }
        }
        // Prüfen, ob $imageSource ein gültiger Pfad ist
        elseif (is_string($imageSource) && strpos($imageSource, '/') !== false) {
            $relativePath = ltrim($imageSource, '/');
            $testPath = $rootDir . '/' . $relativePath;
            
            if (file_exists($testPath)) {
                $absolutePath = $testPath;
                // Versuche, das FilesModel anhand des relativen Pfads zu finden
                $imageObject = FilesModel::findByPath($relativePath);
            } else {
                return ''; // Pfad ungültig oder Datei nicht gefunden
            }
        } else {
            return ''; // Weder gültige UUID noch gültiger Pfad
        }

        // Wenn kein absolutePath gefunden wurde
        if (!$absolutePath || !file_exists($absolutePath)) {
            return '';
        }

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
            // Bildbreite bestimmen für Qualitätsanpassung
            $width = $config->getWidth() ?: 0;
            if ($width === 0 && file_exists($absolutePath)) {
                if ($imageInfo = @getimagesize($absolutePath)) {
                    $width = (int)$imageInfo[0];
                }
            }
            
            $processedImage = self::getContainer()
                ->get('contao.image.factory')
                ->create(
                    $absolutePath,
                    $config,
                    self::getResizeOptions(null, $width, 1)
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

        // Prüfen, ob $source eine UUID ist (String oder binär) und FilesModel laden
        if ((is_string($source) && Uuid::isValid($source)) || 
            (is_string($source) && strlen($source) === 16)) {
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

        // Alt-Text bestimmen: Meta-Daten haben Priorität, dann Parameter
        $metaAlt = (isset($meta['alt']) && !empty(trim($meta['alt']))) ? trim($meta['alt']) : null;
        $finalAlt = self::cleanAttribute($metaAlt ?: $alt);
        // Titel bestimmen: Priorität hat Metadaten-Titel, dann $finalAlt
        $svgTitle = self::cleanAttribute($finalTitle ?: $finalAlt);


        // Style basierend auf Größe - für SVG nur Breite setzen, damit Seitenverhältnis erhalten bleibt
        $style = '';
        if (is_array($size)) {
            if (!empty($size[0])) {
                $style .= 'width: ' . (int)$size[0] . 'px;';
            }
            // Höhe wird bei SVG nicht gesetzt, um das natürliche Seitenverhältnis zu erhalten
            // Das SVG skaliert automatisch basierend auf der viewBox
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
