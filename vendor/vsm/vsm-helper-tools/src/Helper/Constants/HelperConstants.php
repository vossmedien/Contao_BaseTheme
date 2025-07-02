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

namespace Vsm\VsmHelperTools\Helper\Constants;

/**
 * Helper Constants
 * 
 * Zentrale Verwaltung aller Konstanten die in den Helpern verwendet werden.
 * Dies verbessert die Wartbarkeit und vermeidet Duplikation.
 */
class HelperConstants
{
    // Bild-Verarbeitung
    public const DEFAULT_IMAGE_QUALITY = 85;
    public const PNG_COMPRESSION_LEVEL = 6;
    public const WEBP_QUALITY = 90;
    public const AVIF_QUALITY = 90;
    
    // Bildgrößen für Social Media
    public const SOCIAL_MEDIA_SIZES = [
        'opengraph' => [1200, 630, 'crop'],
        'twitter_large' => [1200, 600, 'crop'],
        'twitter_summary' => [400, 400, 'crop'],
        'facebook' => [1200, 630, 'crop'],
        'linkedin' => [1200, 627, 'crop']
    ];
    
    // Responsive Breakpoints
    public const RESPONSIVE_BREAKPOINTS = [
        'xs' => 576,
        'sm' => 768,
        'md' => 992,
        'lg' => 1200,
        'xl' => 1600
    ];
    
    // Video-Einstellungen
    public const VIDEO_FORMATS = ['webm', 'mp4'];
    public const DEFAULT_VIDEO_PARAMS = 'autoplay muted loop playsinline';
    
    // Unterstützte Bildformate
    public const STANDARD_IMAGE_FORMATS = ['jpg', 'jpeg', 'png', 'gif'];
    public const MODERN_IMAGE_FORMATS = ['webp', 'avif'];
    public const ALL_IMAGE_FORMATS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'];
    
    // Cache-Einstellungen
    public const MAX_CACHE_SIZE = 100;
    public const CACHE_TTL = 3600; // 1 Stunde
    
    // Text-Längen für Social Media
    public const SOCIAL_MEDIA_TEXT_LIMITS = [
        'og_title' => 60,
        'og_description' => 160,
        'twitter_title' => 70,
        'twitter_description' => 200,
        'image_alt' => 100
    ];
    
    // Button-Standardwerte
    public const DEFAULT_BUTTON_ANIMATION = 'animate__fadeInUp';
    public const DEFAULT_BUTTON_SIZE = '';
    public const DEFAULT_BUTTON_TYPE = 'btn-primary';
    
    // Headline-Standardwerte
    public const DEFAULT_HEADLINE_TYPE = 'h2';
    public const VALID_HEADLINE_TYPES = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
    
    // Dateipfade
    public const FALLBACK_IMAGE_PATH = 'files/base/layout/img/social-media-fallback.jpg';
    public const VIDEO_PLACEHOLDER_PATH = 'files/base/layout/img/video-placeholder.jpg';
    public const CONVERTED_IMAGES_DIR = 'assets/images/converted';
    
    // Analytics/Tracking
    public const DEFAULT_TRACKING_EVENT = 'Button';
    public const TRACKING_CATEGORIES = ['Button', 'Link', 'Download', 'Video', 'Form'];
    
    // Schema.org Einstellungen
    public const SCHEMA_ENABLED_BY_DEFAULT = true;
    public const SCHEMA_IMAGE_TYPES = ['ImageObject', 'Photograph', 'ImageGallery'];
    public const SCHEMA_REQUIRED_IMAGE_FIELDS = ['contentUrl', 'url'];
} 