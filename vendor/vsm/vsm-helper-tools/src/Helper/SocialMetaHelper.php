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

use Contao\System;
use Contao\PageModel;
use Contao\FilesModel;
use Contao\StringUtil;
use Vsm\VsmHelperTools\Helper\ImageHelper;

/**
 * Social Media Meta-Tags Helper
 *
 * Diese Klasse sammelt Daten von mehreren Elementen auf einer Seite und wählt
 * automatisch das beste verfügbare Bild für Social Media Meta-Tags aus.
 *
 * Bildpriorität:
 * 1. Echte Bilder aus Elementen (Score: 10)
 * 2. Fallback-Bild (Score: 1)
 *
 * Meta-Tags werden nur einmal pro Seite generiert (Singleton-Pattern).
 */
class SocialMetaHelper
{
    // Optimale Bildgrößen für Social Media
    private const SOCIAL_MEDIA_SIZES = [
        'opengraph' => [1200, 630, 'crop'], // Standard OpenGraph 1.91:1 Ratio
        'twitter_large' => [1200, 600, 'crop'], // Twitter Large Card
        'twitter_summary' => [400, 400, 'crop'], // Twitter Summary Card (Square)
        'facebook' => [1200, 630, 'crop'], // Facebook Posts
        'linkedin' => [1200, 627, 'crop'], // LinkedIn Posts
    ];

    // Cache für verarbeitete Meta-Daten
    private static $metaCache = [];

    // Singleton-Logic: Verhindert mehrfache Ausführung pro Seite
    private static $hasGeneratedMetaTags = false;

    // Sammelt alle Element-Daten für beste Bildauswahl
    private static $collectedElementData = [];

    /**
     * Container-Zugriff
     */
    private static function getContainer()
    {
        return System::getContainer();
    }

    /**
     * Bereinigt Text für Social Media Meta-Tags
     */
    private static function cleanText(string $text, int $maxLength = 160): string
    {
        if (empty($text)) {
            return '';
        }

        // HTML-Tags entfernen
        $text = strip_tags($text);

        // Entities dekodieren
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Weiche Bindestriche und <wbr> entfernen
        $text = str_replace(["\xC2\xAD", "­", "<wbr>"], '', $text);

        // Mehrfache Leerzeichen normalisieren
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Text kürzen wenn nötig
        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength - 3) . '...';
        }

        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generiert Fallback Social Media Bild wenn kein anderes Bild vorhanden ist
     */
    private static function generateFallbackSocialImage(): string
    {
        // Verschiedene mögliche Fallback-Bilder prüfen
        $fallbackImages = [
            'files/base/layout/img/social-media-fallback.webp'
        ];

        $container = self::getContainer();
        $rootDir = $container->getParameter('kernel.project_dir');

        foreach ($fallbackImages as $fallbackImage) {
            if (file_exists($rootDir . '/' . $fallbackImage)) {
                return $fallbackImage;
            }
        }

        return '';
    }

    /**
     * Prüft ob eine Bilddatei ein SVG ist (nicht geeignet für Social Media)
     */
    private static function isSvgImage($imageSource): bool
    {
        if (empty($imageSource)) {
            return false;
        }

        // Prüfe Dateiendung
        if (is_string($imageSource)) {
            if (preg_match('/\.svg$/i', $imageSource)) {
                return true;
            }
        }

        // Prüfe über FilesModel wenn UUID
        try {
            if ($fileModel = \Contao\FilesModel::findByUuid($imageSource)) {
                $extension = strtolower(pathinfo($fileModel->path, PATHINFO_EXTENSION));
                return $extension === 'svg';
            }
        } catch (\Exception $e) {
            // Fehler ignorieren, weitermachen
        }

        return false;
    }

    /**
     * Generiert optimiertes Social Media Bild
     */
    private static function generateSocialImage($imageSource, string $type = 'opengraph'): string
    {
        if (empty($imageSource)) {
            return '';
        }

        // SVG-Dateien ablehnen - nicht geeignet für Social Media
        if (self::isSvgImage($imageSource)) {
            return '';
        }

        // UUID-Validierung: Korrupte UUIDs abfangen
        if (is_string($imageSource)) {
            // Prüfe auf binäre oder korrupte Daten
            if (!mb_check_encoding($imageSource, 'UTF-8')) {
                return '';
            }
            
            // Prüfe auf seltsame Zeichen, die nicht in validen UUIDs oder Pfaden vorkommen
            if (preg_match('/[^\x20-\x7E\-\/\.]/', $imageSource)) {
                return '';
            }
        }

        $size = self::SOCIAL_MEDIA_SIZES[$type] ?? self::SOCIAL_MEDIA_SIZES['opengraph'];

        try {
            $imageUrl = ImageHelper::generateImageURL($imageSource, $size);
            


            if ($imageUrl) {
                $container = self::getContainer();
                $request = $container->get('request_stack')->getCurrentRequest();

                if ($request) {
                    $scheme = $request->getScheme();
                    $host = $request->getHttpHost();
                    $fullUrl = $scheme . '://' . $host . $imageUrl;
                    

                    
                    return $fullUrl;
                }
            }
        } catch (\Exception $e) {
            // Fallback: Original-Bild verwenden
            if ($fileModel = \Contao\FilesModel::findByUuid($imageSource)) {
                $container = self::getContainer();
                $request = $container->get('request_stack')->getCurrentRequest();

                if ($request) {
                    $scheme = $request->getScheme();
                    $host = $request->getHttpHost();
                    $fallbackUrl = $scheme . '://' . $host . '/' . $fileModel->path;
                    
                    return $fallbackUrl;
                }
            }
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
     * Ermittelt Site-Name basierend auf der aktuellen Seite
     */
    private static function getSiteName(): string
    {
        global $objPage;

        if ($objPage && $objPage->rootTitle) {
            return $objPage->rootTitle;
        }

        // Fallback aus Konfiguration
        $container = self::getContainer();
        $request = $container->get('request_stack')->getCurrentRequest();

        if ($request) {
            return $request->getHttpHost();
        }

        return 'Website';
    }

    /**
     * Generiert OpenGraph Meta-Tags
     */
    public static function generateOpenGraphTags(array $data): string
    {
        $cacheKey = 'og_' . md5(serialize($data));
        if (isset(self::$metaCache[$cacheKey])) {
            return self::$metaCache[$cacheKey];
        }

        $tags = [];

        // Grundlegende OpenGraph Tags
        $tags[] = '<meta property="og:type" content="' . ($data['type'] ?? 'website') . '">';

        if (!empty($data['title'])) {
            $tags[] = '<meta property="og:title" content="' . self::cleanText($data['title'], 60) . '">';
        }

        if (!empty($data['description'])) {
            $tags[] = '<meta property="og:description" content="' . self::cleanText($data['description'], 160) . '">';
        }

        $url = $data['url'] ?? self::getCurrentUrl();
        if ($url) {
            $tags[] = '<meta property="og:url" content="' . htmlspecialchars($url) . '">';
        }

        $siteName = $data['site_name'] ?? self::getSiteName();
        if ($siteName) {
            $tags[] = '<meta property="og:site_name" content="' . htmlspecialchars($siteName) . '">';
        }

        // Bild für OpenGraph
        if (!empty($data['image'])) {
            $imageUrl = self::generateSocialImage($data['image'], 'opengraph');
            if ($imageUrl) {
                $tags[] = '<meta property="og:image" content="' . htmlspecialchars($imageUrl) . '">';
                $tags[] = '<meta property="og:image:width" content="1200">';
                $tags[] = '<meta property="og:image:height" content="630">';
                $tags[] = '<meta property="og:image:type" content="image/jpeg">';

                if (!empty($data['image_alt'])) {
                    $tags[] = '<meta property="og:image:alt" content="' . self::cleanText($data['image_alt'], 100) . '">';
                }
            }
        }

        // Sprache
        if (!empty($data['locale'])) {
            $tags[] = '<meta property="og:locale" content="' . htmlspecialchars($data['locale']) . '">';
        }

        $result = implode("\n", $tags);
        self::$metaCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Generiert Twitter Card Meta-Tags
     */
    public static function generateTwitterCardTags(array $data): string
    {
        $cacheKey = 'twitter_' . md5(serialize($data));
        if (isset(self::$metaCache[$cacheKey])) {
            return self::$metaCache[$cacheKey];
        }

        $tags = [];

        // Twitter Card Type
        $cardType = 'summary_large_image';
        if (!empty($data['image'])) {
            $cardType = $data['twitter_card_type'] ?? 'summary_large_image';
        } else {
            $cardType = 'summary';
        }

        $tags[] = '<meta name="twitter:card" content="' . $cardType . '">';

        if (!empty($data['title'])) {
            $tags[] = '<meta name="twitter:title" content="' . self::cleanText($data['title'], 70) . '">';
        }

        if (!empty($data['description'])) {
            $tags[] = '<meta name="twitter:description" content="' . self::cleanText($data['description'], 200) . '">';
        }

        // Twitter Bild
        if (!empty($data['image'])) {
            $imageType = $cardType === 'summary' ? 'twitter_summary' : 'twitter_large';
            $imageUrl = self::generateSocialImage($data['image'], $imageType);
            if ($imageUrl) {
                $tags[] = '<meta name="twitter:image" content="' . htmlspecialchars($imageUrl) . '">';

                if (!empty($data['image_alt'])) {
                    $tags[] = '<meta name="twitter:image:alt" content="' . self::cleanText($data['image_alt'], 100) . '">';
                }
            }
        }

        // Optional: Twitter Account
        if (!empty($data['twitter_site'])) {
            $tags[] = '<meta name="twitter:site" content="' . htmlspecialchars($data['twitter_site']) . '">';
        }

        if (!empty($data['twitter_creator'])) {
            $tags[] = '<meta name="twitter:creator" content="' . htmlspecialchars($data['twitter_creator']) . '">';
        }

        $result = implode("\n", $tags);
        self::$metaCache[$cacheKey] = $result;

        return $result;
    }

    /**
     * Generiert zusätzliche SEO Meta-Tags
     */
    public static function generateAdditionalSEOTags(array $data): string
    {
        $tags = [];

        // Robots Meta-Tag (falls nicht bereits gesetzt)
        if (!empty($data['robots'])) {
            $tags[] = '<meta name="robots" content="' . htmlspecialchars($data['robots']) . '">';
        }

        // Keywords Meta-Tag (falls vorhanden)
        if (!empty($data['keywords'])) {
            $keywords = is_array($data['keywords']) ? implode(', ', $data['keywords']) : $data['keywords'];
            $tags[] = '<meta name="keywords" content="' . self::cleanText($keywords, 250) . '">';
        }

        // Author Meta-Tag
        if (!empty($data['author'])) {
            $tags[] = '<meta name="author" content="' . self::cleanText($data['author'], 100) . '">';
        }

        // Canonical URL (falls nicht bereits gesetzt)
        if (!empty($data['canonical'])) {
            $tags[] = '<link rel="canonical" href="' . htmlspecialchars($data['canonical']) . '">';
        }

        // Hreflang für mehrsprachige Seiten
        if (!empty($data['hreflang']) && is_array($data['hreflang'])) {
            foreach ($data['hreflang'] as $lang => $url) {
                $tags[] = '<link rel="alternate" hreflang="' . htmlspecialchars($lang) . '" href="' . htmlspecialchars($url) . '">';
            }
        }

        // Preconnect für externe Domains
        $externalDomains = ['fonts.googleapis.com', 'fonts.gstatic.com', 'www.google-analytics.com', 'www.googletagmanager.com'];
        foreach ($externalDomains as $domain) {
            $tags[] = '<link rel="preconnect" href="https://' . $domain . '">';
        }

        return implode("\n", $tags);
    }

    /**
     * Generiert zusätzliche Social Media Meta-Tags
     */
    public static function generateAdditionalSocialTags(array $data): string
    {
        $tags = [];

        // LinkedIn-spezifische Tags
        if (!empty($data['image'])) {
            $linkedInImage = self::generateSocialImage($data['image'], 'linkedin');
            if ($linkedInImage) {
                $tags[] = '<meta property="og:image:width" content="1200">';
                $tags[] = '<meta property="og:image:height" content="627">';
            }
        }

        // Facebook-spezifische Tags
        if (!empty($data['facebook_app_id'])) {
            $tags[] = '<meta property="fb:app_id" content="' . htmlspecialchars($data['facebook_app_id']) . '">';
        }

        // Schema.org JSON-LD für WebPage
        if (!empty($data['title']) || !empty($data['description'])) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage'
            ];

            if (!empty($data['title'])) {
                $schema['name'] = strip_tags($data['title']);
            }

            if (!empty($data['description'])) {
                $schema['description'] = strip_tags($data['description']);
            }

            $url = $data['url'] ?? self::getCurrentUrl();
            if ($url) {
                $schema['url'] = $url;
            }

            if (!empty($data['image'])) {
                $imageUrl = self::generateSocialImage($data['image'], 'opengraph');
                if ($imageUrl) {
                    $schema['image'] = $imageUrl;
                }
            }

            $tags[] = '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        }

        return implode("\n", $tags);
    }

    /**
     * Sammelt Element-Daten und wählt das beste Bild aus
     */
    public static function collectElementData(array $data): void
    {
        // SVG-Bilder entfernen - nicht geeignet für Social Media
        if (!empty($data['image']) && self::isSvgImage($data['image'])) {
            $data['image'] = '';
        }

        // Bewertung der Bildqualität: echte Bilder > Fallback
        $imageScore = 0;
        $fallbackPath = self::generateFallbackSocialImage();

        if (!empty($data['image'])) {
            if ($data['image'] === $fallbackPath) {
                $imageScore = 1; // Fallback-Bild
            } else {
                $imageScore = 10; // Echtes Bild
            }
        }

        $data['_imageScore'] = $imageScore;
        self::$collectedElementData[] = $data;
    }

    /**
     * Extrahiert Meta-Daten aus der aktuellen Seite (Contao PageModel)
     */
    private static function getPageMetaData(): array
    {
        global $objPage;
        
        $pageData = [];
        
        if ($objPage) {
            // Seiten-Titel hat höchste Priorität
            if (!empty($objPage->pageTitle)) {
                $pageData['title'] = $objPage->pageTitle;
            } elseif (!empty($objPage->title)) {
                $pageData['title'] = $objPage->title;
            }
            
            // Seiten-Beschreibung hat höchste Priorität
            if (!empty($objPage->description)) {
                $pageData['description'] = $objPage->description;
            }
            
            // Weitere Meta-Informationen
            if (!empty($objPage->language)) {
                $locale = str_replace('-', '_', $objPage->language);
                $pageData['locale'] = $locale;
            }
            
            // Keywords falls vorhanden
            if (!empty($objPage->keywords)) {
                $pageData['keywords'] = $objPage->keywords;
            }
            
            // Robots falls vorhanden
            if (!empty($objPage->robots)) {
                $pageData['robots'] = $objPage->robots;
            }
        }
        
        return $pageData;
    }

    /**
     * Ermittelt die besten verfügbaren Daten aus allen gesammelten Elementen
     */
    private static function getBestElementData(): array
    {
        // Seiten-Meta-Daten haben immer Priorität
        $pageData = self::getPageMetaData();
        
        if (empty(self::$collectedElementData)) {
            return $pageData;
        }

        // Sortiere nach Bildqualität (höchster Score zuerst)
        usort(self::$collectedElementData, function($a, $b) {
            return ($b['_imageScore'] ?? 0) <=> ($a['_imageScore'] ?? 0);
        });

        $bestElementData = self::$collectedElementData[0];

        // Kombiniere: Seiten-Meta-Daten haben Priorität, Element-Daten als Fallback
        $finalData = [];
        
        // Titel: Seite > Element-Daten
        if (!empty($pageData['title'])) {
            $finalData['title'] = $pageData['title'];
        } elseif (!empty($bestElementData['title'])) {
            $finalData['title'] = $bestElementData['title'];
        } else {
            // Fallback: Titel aus anderen Elementen suchen
            foreach (self::$collectedElementData as $elementData) {
                if (!empty($elementData['title'])) {
                    $finalData['title'] = $elementData['title'];
                    break;
                }
            }
        }

        // Beschreibung: Seite > Element-Daten
        if (!empty($pageData['description'])) {
            $finalData['description'] = $pageData['description'];
        } elseif (!empty($bestElementData['description'])) {
            $finalData['description'] = $bestElementData['description'];
        } else {
            // Fallback: Beschreibung aus anderen Elementen suchen
            foreach (self::$collectedElementData as $elementData) {
                if (!empty($elementData['description'])) {
                    $finalData['description'] = $elementData['description'];
                    break;
                }
            }
        }
        
        // Bild: Verwende das beste verfügbare Bild aus Elementen
        if (!empty($bestElementData['image'])) {
            $finalData['image'] = $bestElementData['image'];
            
            // Alt-Text für Bild: Priorität Seiten-Titel > Element-Titel
            if (!empty($finalData['title'])) {
                $finalData['image_alt'] = $finalData['title'];
            } elseif (!empty($bestElementData['image_alt'])) {
                $finalData['image_alt'] = $bestElementData['image_alt'];
            }
        }

        // Fallback-Bild setzen wenn kein Bild aus Elementen gefunden wurde
        if (empty($finalData['image'])) {
            $fallbackImage = self::generateFallbackSocialImage();
            if ($fallbackImage) {
                $finalData['image'] = $fallbackImage;
                
                // Alt-Text für Fallback-Bild
                if (!empty($finalData['title'])) {
                    $finalData['image_alt'] = $finalData['title'];
                }
            }
        }
        
        // Weitere Meta-Daten von der Seite übernehmen
        foreach (['locale', 'keywords', 'robots', 'author', 'type'] as $field) {
            if (!empty($pageData[$field])) {
                $finalData[$field] = $pageData[$field];
            } elseif (!empty($bestElementData[$field])) {
                $finalData[$field] = $bestElementData[$field];
            }
        }

        return $finalData;
    }

    /**
     * Hauptmethode: Generiert alle Social Media Meta-Tags
     */
    public static function generateSocialMetaTags(array $data): string
    {
        // Sammle Element-Daten (nur wenn nicht leer)
        if (!empty($data)) {
            self::collectElementData($data);
        }

        // Singleton-Check: Nur einmal pro Seite ausführen
        if (self::$hasGeneratedMetaTags) {
            return '';
        }

        self::$hasGeneratedMetaTags = true;

        // Verwende die besten verfügbaren Daten
        $bestData = self::getBestElementData();
        if (empty($bestData)) {
            $bestData = $data; // Fallback auf ursprüngliche Daten
        }

        $allTags = [];

        // Zusätzliche SEO Meta-Tags
        $seoTags = self::generateAdditionalSEOTags($bestData);
        if ($seoTags) {
            $allTags[] = '<!-- Additional SEO Meta Tags -->';
            $allTags[] = $seoTags;
        }

        // OpenGraph Tags
        $ogTags = self::generateOpenGraphTags($bestData);
        if ($ogTags) {
            $allTags[] = '<!-- OpenGraph Meta Tags -->';
            $allTags[] = $ogTags;
        }

        // Twitter Card Tags
        $twitterTags = self::generateTwitterCardTags($bestData);
        if ($twitterTags) {
            $allTags[] = '<!-- Twitter Card Meta Tags -->';
            $allTags[] = $twitterTags;
        }

        // Zusätzliche Social Media Tags
        $additionalTags = self::generateAdditionalSocialTags($bestData);
        if ($additionalTags) {
            $allTags[] = '<!-- Additional Social Media Tags -->';
            $allTags[] = $additionalTags;
        }

        return implode("\n", $allTags);
    }

    /**
     * Hilfsmethode: Extrahiert Social Media Daten aus Template-Objekten
     * Seiten-Meta-Daten haben Priorität über Element-Daten
     */
    public static function extractDataFromTemplate($templateObject): array
    {
        // Zuerst Seiten-Meta-Daten holen
        $pageData = self::getPageMetaData();
        $data = [];

        // Titel: Seiten-Meta hat Priorität
        if (!empty($pageData['title'])) {
            $data['title'] = $pageData['title'];
        } elseif (!empty($templateObject->headline)) {
            $data['title'] = $templateObject->headline;
        } elseif (!empty($templateObject->main_headline)) {
            $data['title'] = $templateObject->main_headline;
        } elseif (!empty($templateObject->top_headline)) {
            $data['title'] = $templateObject->top_headline;
        }

        // Beschreibung: Seiten-Meta hat Priorität
        if (!empty($pageData['description'])) {
            $data['description'] = $pageData['description'];
        } elseif (!empty($templateObject->text)) {
            $data['description'] = $templateObject->text;
        } elseif (!empty($templateObject->main_text)) {
            $data['description'] = $templateObject->main_text;
        } elseif (!empty($templateObject->subline)) {
            $data['description'] = $templateObject->subline;
        } elseif (!empty($templateObject->left_text_below_headline)) {
            $data['description'] = $templateObject->left_text_below_headline;
        }

        // Bild aus verschiedenen möglichen Feldern (Element-Daten für Bilder)
        if (!empty($templateObject->image_src)) {
            $data['image'] = $templateObject->image_src;
        } elseif (!empty($templateObject->main_image)) {
            $data['image'] = $templateObject->main_image;
        } elseif (!empty($templateObject->image)) {
            $data['image'] = $templateObject->image;
        } elseif (!empty($templateObject->header_image)) {
            $data['image'] = $templateObject->header_image;
        }

        // SVG-Bilder ablehnen - nicht geeignet für Social Media
        if (!empty($data['image']) && self::isSvgImage($data['image'])) {
            $data['image'] = '';
        }

        // Fallback-Bild verwenden wenn kein Bild gefunden wurde
        if (empty($data['image'])) {
            $fallbackImage = self::generateFallbackSocialImage();
            if ($fallbackImage) {
                $data['image'] = $fallbackImage;
            }
        }

        // Alt-Text für Bild: Priorität Seiten-Titel > Element-Titel
        if (!empty($data['image'])) {
            if (!empty($data['title'])) {
                $data['image_alt'] = $data['title'];
            } elseif (!empty($templateObject->headline)) {
                $data['image_alt'] = $templateObject->headline;
            }
        }

        // Weitere Meta-Daten von der Seite übernehmen
        foreach (['locale', 'keywords', 'robots'] as $field) {
            if (!empty($pageData[$field])) {
                $data[$field] = $pageData[$field];
            }
        }

        return $data;
    }

    /**
     * Convenience-Methode für Hero-Templates
     */
    public static function generateHeroSocialMeta($templateObject): string
    {
        $data = self::extractDataFromTemplate($templateObject);

        // Hero-spezifische Anpassungen
        $data['type'] = 'website';

        return self::generateSocialMetaTags($data);
    }

    /**
     * Convenience-Methode für News-Templates
     * Seiten-Meta-Daten haben Priorität über News-Element-Daten
     */
    public static function generateNewsSocialMeta($templateObject): string
    {
        // Zuerst Seiten-Meta-Daten holen
        $pageData = self::getPageMetaData();
        $data = [];

        // Titel: Seiten-Meta hat Priorität über News-Headline
        if (!empty($pageData['title'])) {
            $data['title'] = $pageData['title'];
        } elseif (!empty($templateObject->headline)) {
            $data['title'] = $templateObject->headline;
        }

        // Beschreibung: Seiten-Meta hat Priorität über News-Teaser/Text
        if (!empty($pageData['description'])) {
            $data['description'] = $pageData['description'];
        } elseif (!empty($templateObject->teaser)) {
            // HTML-Tags aus teaser entfernen für Meta-Tags
            $data['description'] = strip_tags($templateObject->teaser);
        } elseif (!empty($templateObject->text)) {
            // Fallback auf text-Inhalt
            $data['description'] = strip_tags($templateObject->text);
        }

        // Hauptbild der News verwenden (nur wenn es kein SVG ist)
        if (!empty($templateObject->src) && !self::isSvgImage($templateObject->src)) {
            $data['image'] = $templateObject->src;
        }

        // Alt-Text für Bild: Priorität Seiten-Titel > News-Titel
        if (!empty($data['image'])) {
            if (!empty($data['title'])) {
                $data['image_alt'] = $data['title'];
            } elseif (!empty($templateObject->headline)) {
                $data['image_alt'] = $templateObject->headline;
            }
        }

        // Autor hinzufügen wenn verfügbar
        if (!empty($templateObject->postAuthor)) {
            $data['author'] = $templateObject->postAuthor;
        }

        // Meta-Daten von der Seite übernehmen
        foreach (['locale', 'keywords', 'robots'] as $field) {
            if (!empty($pageData[$field])) {
                $data[$field] = $pageData[$field];
            }
        }

        // News-spezifische Anpassungen
        $data['type'] = 'article';

        return self::generateSocialMetaTags($data);
    }

    /**
     * Extrahiert Social Media Daten spezifisch für News-Templates mit erweiterten Optionen
     * Seiten-Meta-Daten haben Priorität über News-Element-Daten
     */
    public static function extractNewsDataFromTemplate($templateObject): array
    {
        // Zuerst Seiten-Meta-Daten holen
        $pageData = self::getPageMetaData();
        $data = [];

        // Titel: Seiten-Meta hat Priorität über News-Headline
        if (!empty($pageData['title'])) {
            $data['title'] = $pageData['title'];
        } elseif (!empty($templateObject->headline)) {
            $data['title'] = $templateObject->headline;
        }

        // Beschreibung: Seiten-Meta hat Priorität über teaser > text
        if (!empty($pageData['description'])) {
            $data['description'] = $pageData['description'];
        } elseif (!empty($templateObject->teaser)) {
            $data['description'] = strip_tags($templateObject->teaser);
        } elseif (!empty($templateObject->text)) {
            $data['description'] = strip_tags($templateObject->text);
        }

        // Bild: Priorität src > authorImage
        if (!empty($templateObject->src)) {
            $data['image'] = $templateObject->src;
        } elseif (!empty($templateObject->authorImage)) {
            $data['image'] = $templateObject->authorImage;
        }

        // Video-Support für Ratgeber-Template
        if (!empty($templateObject->videoSRC)) {
            // Bei Videos das Poster-Bild als Social Media Bild verwenden
            if (!empty($templateObject->videoPosterSRC)) {
                $data['image'] = $templateObject->videoPosterSRC;
            }
        }

        // Galerie-Support für Pressemedien-Template
        if (!empty($templateObject->multiSRC) && empty($data['image'])) {
            // Erstes Bild aus der Galerie verwenden
            if (is_string($templateObject->multiSRC)) {
                $galleryImages = \Contao\StringUtil::deserialize($templateObject->multiSRC, true);
                if (!empty($galleryImages) && is_array($galleryImages)) {
                    // Erstes Nicht-SVG-Bild aus der Galerie finden
                    foreach ($galleryImages as $galleryImage) {
                        if (!self::isSvgImage($galleryImage)) {
                            $data['image'] = $galleryImage;
                            break;
                        }
                    }
                }
            }
        }

        // SVG-Bilder ablehnen - nicht geeignet für Social Media
        if (!empty($data['image']) && self::isSvgImage($data['image'])) {
            $data['image'] = '';
        }

        // Fallback-Bild verwenden wenn kein Bild gefunden wurde
        if (empty($data['image'])) {
            $fallbackImage = self::generateFallbackSocialImage();
            if ($fallbackImage) {
                $data['image'] = $fallbackImage;
            }
        }

        // Alt-Text für Bild: Priorität Seiten-Titel > News-Titel
        if (!empty($data['image'])) {
            if (!empty($data['title'])) {
                $data['image_alt'] = $data['title'];
            } elseif (!empty($templateObject->headline)) {
                $data['image_alt'] = $templateObject->headline;
            }
        }

        // Autor hinzufügen
        if (!empty($templateObject->postAuthor)) {
            $data['author'] = $templateObject->postAuthor;
        }

        // Meta-Daten von der Seite übernehmen
        foreach (['locale', 'keywords', 'robots'] as $field) {
            if (!empty($pageData[$field])) {
                $data[$field] = $pageData[$field];
            }
        }

        // News-spezifische Metadaten
        $data['type'] = 'article';

        return $data;
    }

    /**
     * Setzt den Singleton-Status zurück (für Tests oder spezielle Fälle)
     */
    public static function resetSingletonStatus(): void
    {
        self::$hasGeneratedMetaTags = false;
        self::$collectedElementData = [];
    }

    /**
     * Prüft ob bereits Meta-Tags generiert wurden
     */
    public static function hasGeneratedMetaTags(): bool
    {
        return self::$hasGeneratedMetaTags;
    }

    /**
     * Gibt die Anzahl der gesammelten Elemente zurück
     */
    public static function getCollectedElementsCount(): int
    {
        return count(self::$collectedElementData);
    }

    /**
     * Generiert Breadcrumb Schema.org JSON-LD
     */
    public static function generateBreadcrumbSchema(array $breadcrumbs): string
    {
        if (empty($breadcrumbs)) {
            return '';
        }

        $breadcrumbList = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $breadcrumbList['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => self::cleanText($breadcrumb['name'] ?? '', 100),
                'item' => $breadcrumb['url'] ?? ''
            ];
        }

        return '<script type="application/ld+json">' . json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    /**
     * Generiert Website Schema.org JSON-LD für bessere Suchmaschinenoptimierung
     */
    public static function generateWebsiteSchema(array $data): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite'
        ];

        if (!empty($data['name'])) {
            $schema['name'] = self::cleanText($data['name'], 100);
        }

        if (!empty($data['url'])) {
            $schema['url'] = $data['url'];
        }

        if (!empty($data['description'])) {
            $schema['description'] = self::cleanText($data['description'], 200);
        }

        // Suchfunktion hinzufügen falls vorhanden
        if (!empty($data['search_url'])) {
            $schema['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $data['search_url'] . '?q={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ];
        }

        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }
}
