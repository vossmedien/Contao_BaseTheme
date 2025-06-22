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
     * Generiert optimiertes Social Media Bild
     */
    private static function generateSocialImage($imageSource, string $type = 'opengraph'): string
    {
        if (empty($imageSource)) {
            return '';
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
                    return $scheme . '://' . $host . $imageUrl;
                }
            }
        } catch (\Exception $e) {
            // Fallback: Original-Bild verwenden
            if ($fileModel = FilesModel::findByUuid($imageSource)) {
                $container = self::getContainer();
                $request = $container->get('request_stack')->getCurrentRequest();
                
                if ($request) {
                    $scheme = $request->getScheme();
                    $host = $request->getHttpHost();
                    return $scheme . '://' . $host . '/' . $fileModel->path;
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
     * Hauptmethode: Generiert alle Social Media Meta-Tags
     */
    public static function generateSocialMetaTags(array $data): string
    {
        // Singleton-Check: Nur einmal pro Seite ausführen
        if (self::$hasGeneratedMetaTags) {
            return '';
        }
        
        self::$hasGeneratedMetaTags = true;
        
        $allTags = [];
        
        // Zusätzliche SEO Meta-Tags
        $seoTags = self::generateAdditionalSEOTags($data);
        if ($seoTags) {
            $allTags[] = '<!-- Additional SEO Meta Tags -->';
            $allTags[] = $seoTags;
        }
        
        // OpenGraph Tags
        $ogTags = self::generateOpenGraphTags($data);
        if ($ogTags) {
            $allTags[] = '<!-- OpenGraph Meta Tags -->';
            $allTags[] = $ogTags;
        }
        
        // Twitter Card Tags
        $twitterTags = self::generateTwitterCardTags($data);
        if ($twitterTags) {
            $allTags[] = '<!-- Twitter Card Meta Tags -->';
            $allTags[] = $twitterTags;
        }
        
        // Zusätzliche Social Media Tags
        $additionalTags = self::generateAdditionalSocialTags($data);
        if ($additionalTags) {
            $allTags[] = '<!-- Additional Social Media Tags -->';
            $allTags[] = $additionalTags;
        }
        
        return implode("\n", $allTags);
    }

    /**
     * Hilfsmethode: Extrahiert Social Media Daten aus Template-Objekten
     */
    public static function extractDataFromTemplate($templateObject): array
    {
        $data = [];
        
        // Titel aus verschiedenen möglichen Feldern
        if (!empty($templateObject->headline)) {
            $data['title'] = $templateObject->headline;
        } elseif (!empty($templateObject->main_headline)) {
            $data['title'] = $templateObject->main_headline;
        } elseif (!empty($templateObject->top_headline)) {
            $data['title'] = $templateObject->top_headline;
        }
        
        // Beschreibung aus verschiedenen möglichen Feldern
        if (!empty($templateObject->text)) {
            $data['description'] = $templateObject->text;
        } elseif (!empty($templateObject->main_text)) {
            $data['description'] = $templateObject->main_text;
        } elseif (!empty($templateObject->subline)) {
            $data['description'] = $templateObject->subline;
        } elseif (!empty($templateObject->left_text_below_headline)) {
            $data['description'] = $templateObject->left_text_below_headline;
        }
        
        // Bild aus verschiedenen möglichen Feldern
        if (!empty($templateObject->image_src)) {
            $data['image'] = $templateObject->image_src;
        } elseif (!empty($templateObject->main_image)) {
            $data['image'] = $templateObject->main_image;
        } elseif (!empty($templateObject->image)) {
            $data['image'] = $templateObject->image;
        } elseif (!empty($templateObject->header_image)) {
            $data['image'] = $templateObject->header_image;
        }
        
        // Alt-Text für Bild
        if (!empty($data['image']) && !empty($data['title'])) {
            $data['image_alt'] = $data['title'];
        }
        
        // Sprache
        global $objPage;
        if ($objPage && $objPage->language) {
            $locale = str_replace('-', '_', $objPage->language);
            $data['locale'] = $locale;
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
     */
    public static function generateNewsSocialMeta($templateObject): string
    {
        $data = [];
        
        // Titel aus verfügbaren News-Feldern
        if (!empty($templateObject->headline)) {
            $data['title'] = $templateObject->headline;
        }
        
        // Beschreibung aus verfügbaren News-Feldern
        if (!empty($templateObject->teaser)) {
            // HTML-Tags aus teaser entfernen für Meta-Tags
            $data['description'] = strip_tags($templateObject->teaser);
        } elseif (!empty($templateObject->text)) {
            // Fallback auf text-Inhalt
            $data['description'] = strip_tags($templateObject->text);
        }
        
        // Hauptbild der News verwenden
        if (!empty($templateObject->src)) {
            $data['image'] = $templateObject->src;
        }
        
        // Alt-Text für Bild
        if (!empty($data['image']) && !empty($data['title'])) {
            $data['image_alt'] = $data['title'];
        }
        
        // Autor hinzufügen wenn verfügbar
        if (!empty($templateObject->postAuthor)) {
            $data['author'] = $templateObject->postAuthor;
        }
        
        // Sprache
        global $objPage;
        if ($objPage && $objPage->language) {
            $locale = str_replace('-', '_', $objPage->language);
            $data['locale'] = $locale;
        }
        
        // News-spezifische Anpassungen
        $data['type'] = 'article';
        
        return self::generateSocialMetaTags($data);
    }

    /**
     * Extrahiert Social Media Daten spezifisch für News-Templates mit erweiterten Optionen
     */
    public static function extractNewsDataFromTemplate($templateObject): array
    {
        $data = [];
        
        // Titel aus News-Template
        if (!empty($templateObject->headline)) {
            $data['title'] = $templateObject->headline;
        }
        
        // Beschreibung: Priorität teaser > text
        if (!empty($templateObject->teaser)) {
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
                    $data['image'] = $galleryImages[0];
                }
            }
        }
        
        // Alt-Text für Bild
        if (!empty($data['image']) && !empty($data['title'])) {
            $data['image_alt'] = $data['title'];
        }
        
        // Autor hinzufügen
        if (!empty($templateObject->postAuthor)) {
            $data['author'] = $templateObject->postAuthor;
        }
        
        // Sprache
        global $objPage;
        if ($objPage && $objPage->language) {
            $locale = str_replace('-', '_', $objPage->language);
            $data['locale'] = $locale;
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
    }

    /**
     * Prüft ob bereits Meta-Tags generiert wurden
     */
    public static function hasGeneratedMetaTags(): bool
    {
        return self::$hasGeneratedMetaTags;
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