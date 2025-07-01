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

use Vsm\VsmHelperTools\Helper\Traits\HelperTrait;
use Vsm\VsmHelperTools\Helper\Traits\DebuggableTrait;
use Vsm\VsmHelperTools\Helper\Constants\HelperConstants;
use Contao\FilesModel;
use Contao\StringUtil;
use Symfony\Component\Uid\Uuid;

/**
 * Schema.org Helper
 * 
 * Generiert strukturierte Daten nach Schema.org Standard für
 * bessere Suchmaschinen-Indexierung und Rich Snippets.
 */
class SchemaOrgHelper
{
    use HelperTrait;
    use DebuggableTrait;
    
    /**
     * Generiert Schema.org ImageObject JSON-LD
     * 
     * @param string|object $imageSource UUID, Pfad oder FilesModel
     * @param array $options Zusätzliche Optionen
     * @return string JSON-LD Script-Tag oder leerer String
     */
    public static function generateImageSchema($imageSource, array $options = []): string
    {
        try {
            self::debug('Generiere Image Schema', ['source' => $imageSource]);
            
            // Bild-Informationen laden
            $imageData = self::loadImageData($imageSource);
            if (!$imageData) {
                return '';
            }
            
            // Schema-Daten aufbauen
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'ImageObject'
            ];
            
            // Basis-URL ermitteln
            $baseUrl = self::getBaseUrl();
            
            // Pflichtfelder
            $schema['contentUrl'] = $baseUrl . '/' . ltrim($imageData['path'], '/');
            $schema['url'] = $schema['contentUrl'];
            
            // Optionale Felder aus Metadaten
            if (!empty($imageData['meta']['title'])) {
                $schema['name'] = strip_tags($imageData['meta']['title']);
            }
            
            if (!empty($imageData['meta']['alt'])) {
                $schema['description'] = strip_tags($imageData['meta']['alt']);
            } elseif (!empty($imageData['meta']['caption'])) {
                $schema['description'] = strip_tags($imageData['meta']['caption']);
            }
            
            if (!empty($imageData['meta']['caption'])) {
                $schema['caption'] = strip_tags($imageData['meta']['caption']);
            }
            
            // Bildabmessungen
            if (!empty($imageData['width']) && !empty($imageData['height'])) {
                $schema['width'] = [
                    '@type' => 'QuantitativeValue',
                    'value' => $imageData['width'],
                    'unitCode' => 'PX'
                ];
                $schema['height'] = [
                    '@type' => 'QuantitativeValue', 
                    'value' => $imageData['height'],
                    'unitCode' => 'PX'
                ];
            }
            
            // Dateigröße
            if (!empty($imageData['filesize'])) {
                $schema['contentSize'] = self::formatFileSize($imageData['filesize']);
            }
            
            // MIME-Type
            if (!empty($imageData['mime'])) {
                $schema['encodingFormat'] = $imageData['mime'];
            }
            
            // Upload/Modifikationsdatum
            if (!empty($imageData['tstamp'])) {
                $schema['uploadDate'] = date('c', $imageData['tstamp']);
                $schema['dateModified'] = date('c', $imageData['tstamp']);
            }
            
            // Lizenz (falls vorhanden)
            if (!empty($options['license'])) {
                $schema['license'] = $options['license'];
            }
            
            // Urheber/Fotograf
            if (!empty($options['creator'])) {
                $schema['creator'] = [
                    '@type' => 'Person',
                    'name' => $options['creator']
                ];
            } elseif (!empty($imageData['meta']['photographer'])) {
                $schema['creator'] = [
                    '@type' => 'Person',
                    'name' => $imageData['meta']['photographer']
                ];
            }
            
            // Copyright
            if (!empty($options['copyrightHolder'])) {
                $schema['copyrightHolder'] = [
                    '@type' => 'Organization',
                    'name' => $options['copyrightHolder']
                ];
            } elseif (!empty($imageData['meta']['copyright'])) {
                $schema['copyrightHolder'] = [
                    '@type' => 'Organization',
                    'name' => $imageData['meta']['copyright']
                ];
            }
            
            // Thumbnail (falls vorhanden)
            if (!empty($options['thumbnail'])) {
                $schema['thumbnail'] = [
                    '@type' => 'ImageObject',
                    'contentUrl' => $baseUrl . '/' . ltrim($options['thumbnail'], '/')
                ];
            }
            
            // Keywords/Tags
            if (!empty($options['keywords'])) {
                $schema['keywords'] = is_array($options['keywords']) ? 
                    implode(', ', $options['keywords']) : $options['keywords'];
            }
            
            // Repräsentatives Bild für andere Inhalte
            if (!empty($options['representativeOfPage']) && $options['representativeOfPage'] === true) {
                $schema['representativeOfPage'] = true;
            }
            
            // In größeren Kontext einbetten (z.B. Artikel)
            if (!empty($options['isPartOf'])) {
                $schema['isPartOf'] = $options['isPartOf'];
            }
            
            self::debug('Image Schema generiert', ['schema' => $schema]);
            
            return self::wrapInScriptTag($schema);
            
        } catch (\Exception $e) {
            self::logError('Fehler beim Generieren des Image Schemas', [
                'error' => $e->getMessage(),
                'source' => $imageSource
            ]);
            return '';
        }
    }
    
    /**
     * Generiert Schema.org MediaObject für Videos
     */
    public static function generateVideoSchema($videoSource, array $options = []): string
    {
        try {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'VideoObject'
            ];
            
            $baseUrl = self::getBaseUrl();
            
            // Pflichtfelder
            $schema['name'] = $options['name'] ?? 'Video';
            $schema['description'] = $options['description'] ?? 'Video content';
            $schema['contentUrl'] = $baseUrl . '/' . ltrim($videoSource, '/');
            $schema['embedUrl'] = $schema['contentUrl'];
            
            // Upload-Datum
            $schema['uploadDate'] = $options['uploadDate'] ?? date('c');
            
            // Thumbnail ist Pflicht für Google
            if (!empty($options['thumbnail'])) {
                $schema['thumbnailUrl'] = $baseUrl . '/' . ltrim($options['thumbnail'], '/');
            }
            
            // Dauer (falls bekannt)
            if (!empty($options['duration'])) {
                $schema['duration'] = $options['duration']; // ISO 8601 Format: PT1M30S
            }
            
            return self::wrapInScriptTag($schema);
            
        } catch (\Exception $e) {
            self::logError('Fehler beim Generieren des Video Schemas', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * Generiert Schema.org WebPage mit primaryImageOfPage
     */
    public static function generateWebPageWithImageSchema(array $pageData, $primaryImage): string
    {
        try {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage'
            ];
            
            // Seiten-Daten
            if (!empty($pageData['name'])) {
                $schema['name'] = $pageData['name'];
            }
            
            if (!empty($pageData['description'])) {
                $schema['description'] = $pageData['description'];
            }
            
            $schema['url'] = $pageData['url'] ?? self::getCurrentUrl();
            
            // Primärbild der Seite
            if ($primaryImage) {
                $imageData = self::loadImageData($primaryImage);
                if ($imageData) {
                    $baseUrl = self::getBaseUrl();
                    $schema['primaryImageOfPage'] = [
                        '@type' => 'ImageObject',
                        'contentUrl' => $baseUrl . '/' . ltrim($imageData['path'], '/')
                    ];
                    
                    if (!empty($imageData['meta']['alt'])) {
                        $schema['primaryImageOfPage']['description'] = $imageData['meta']['alt'];
                    }
                }
            }
            
            return self::wrapInScriptTag($schema);
            
        } catch (\Exception $e) {
            self::logError('Fehler beim Generieren des WebPage Schemas', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * Generiert Schema.org für eine Bilder-Galerie
     */
    public static function generateImageGallerySchema(array $images, array $options = []): string
    {
        try {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'ImageGallery',
                'name' => $options['name'] ?? 'Bildergalerie',
                'description' => $options['description'] ?? ''
            ];
            
            $baseUrl = self::getBaseUrl();
            $imageObjects = [];
            
            foreach ($images as $image) {
                $imageData = self::loadImageData($image);
                if ($imageData) {
                    $imageObject = [
                        '@type' => 'ImageObject',
                        'contentUrl' => $baseUrl . '/' . ltrim($imageData['path'], '/')
                    ];
                    
                    if (!empty($imageData['meta']['alt'])) {
                        $imageObject['description'] = $imageData['meta']['alt'];
                    }
                    
                    $imageObjects[] = $imageObject;
                }
            }
            
            if (!empty($imageObjects)) {
                $schema['image'] = $imageObjects;
                return self::wrapInScriptTag($schema);
            }
            
            return '';
            
        } catch (\Exception $e) {
            self::logError('Fehler beim Generieren des Gallery Schemas', [
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }
    
    /**
     * Lädt Bilddaten aus verschiedenen Quellen
     */
    private static function loadImageData($imageSource): ?array
    {
        try {
            $fileModel = null;
            
            // UUID/Pfad-Validierung: Korrupte Daten abfangen
            if (is_string($imageSource)) {
                // Prüfe auf binäre oder korrupte Daten
                if (!mb_check_encoding($imageSource, 'UTF-8')) {
                    return null;
                }
                
                // Prüfe auf seltsame Zeichen
                if (preg_match('/[^\x20-\x7E\-\/\.]/', $imageSource)) {
                    return null;
                }
            }
            
            if (is_string($imageSource) && class_exists('Symfony\Component\Uid\Uuid') && Uuid::isValid($imageSource)) {
                // UUID
                $fileModel = FilesModel::findByUuid($imageSource);
            } elseif (is_string($imageSource)) {
                // Pfad
                $fileModel = FilesModel::findByPath($imageSource);
            } elseif ($imageSource instanceof FilesModel) {
                $fileModel = $imageSource;
            }
            
            if (!$fileModel) {
                return null;
            }
            
            // Metadaten laden
            $meta = [];
            if ($fileModel->meta) {
                $metaData = StringUtil::deserialize($fileModel->meta, true);
                $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? 'de';
                $meta = $metaData[$currentLanguage] ?? reset($metaData) ?: [];
            }
            
            // Bildabmessungen ermitteln
            $rootDir = self::getContainer()->getParameter('kernel.project_dir');
            $imagePath = $rootDir . '/' . $fileModel->path;
            
            $width = null;
            $height = null;
            
            if (file_exists($imagePath) && @is_file($imagePath)) {
                $imageSize = @getimagesize($imagePath);
                if ($imageSize) {
                    $width = $imageSize[0];
                    $height = $imageSize[1];
                }
            }
            
            return [
                'path' => $fileModel->path,
                'meta' => $meta,
                'width' => $width,
                'height' => $height,
                'filesize' => $fileModel->filesize,
                'mime' => $fileModel->mime,
                'tstamp' => $fileModel->tstamp
            ];
            
        } catch (\Exception $e) {
            self::logWarning('Konnte Bilddaten nicht laden', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Formatiert Dateigröße für Schema.org
     */
    private static function formatFileSize(int $bytes): string
    {
        // Schema.org erwartet Format wie "1.5MB"
        if ($bytes < 1024) {
            return $bytes . 'B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 1) . 'KB';
        } else {
            return round($bytes / 1048576, 1) . 'MB';
        }
    }
    
    /**
     * Ermittelt die Basis-URL
     */
    private static function getBaseUrl(): string
    {
        $container = self::getContainer();
        $request = $container->get('request_stack')->getCurrentRequest();
        
        if ($request) {
            return $request->getSchemeAndHttpHost();
        }
        
        return '';
    }
    
    /**
     * Ermittelt die aktuelle URL
     */
    private static function getCurrentUrl(): string
    {
        $container = self::getContainer();
        $request = $container->get('request_stack')->getCurrentRequest();
        
        if ($request) {
            return $request->getSchemeAndHttpHost() . $request->getRequestUri();
        }
        
        return '';
    }
    
    /**
     * Wickelt Schema-Daten in Script-Tag
     */
    private static function wrapInScriptTag(array $schema): string
    {
        $json = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        return '<script type="application/ld+json">' . $json . '</script>';
    }
} 