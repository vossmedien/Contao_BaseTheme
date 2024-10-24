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
            return $retinaConfig;
        }
        return null;
    }

    private static function generateSource(string $type, string $src, string $retinaSrc, ?string $retina3xSrc = null, ?string $mediaQuery = null): string
    {
        $srcset = $retina3xSrc
            ? "{$src} 1x, {$retinaSrc} 2x, {$retina3xSrc} 3x"
            : "{$src} 1x, {$retinaSrc} 2x";

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

        $processedImage = $imageFactory->create($path, $config, $options);
        $processedPath = $processedImage->getPath();

        $relativePath = str_replace($rootDir, '', $processedPath);
        return [
            'path' => $processedPath,
            'src' => dirname($relativePath) . '/' . rawurlencode(basename($relativePath))
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
        $baseImagePath = dirname($absoluteImagePath) . "/" . rawurlencode(basename($absoluteImagePath));

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

            $canCreate2x = ($requestedWidth * 2 <= $originalWidth) && ($requestedHeight * 2 <= $originalHeight);

            $width = $canCreate2x ? $requestedWidth * 2 : $requestedWidth;
            $height = $canCreate2x ? $requestedHeight * 2 : $requestedHeight;

            if ($width !== null) $config->setWidth($width);
            if ($height !== null) $config->setHeight($height);
            if ($mode !== "") $config->setMode($mode);
        }

        try {
            $baseImage = self::processImage(
                $absoluteImagePath,
                $config,
                self::getResizeOptions()
            );
            $baseWidth = $width ?? $originalWidth;
            $baseHeight = $height ?? $originalHeight;
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

            $config->setWidth($width);
            if ($mode !== "") {
                $config->setMode($mode);
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

                $imageSrc = $processedImage['src'];
                $webpSrc = $webpImage['src'];

                $srcset[] = $imageSrc . ' ' . $width . 'w';
                $webpSrcset[] = $webpSrc . ' ' . $width . 'w';

                // Retina Versionen
                $retinaImageSrc = $imageSrc;
                $retinaWebpSrc = $webpSrc;
                $retina3xImageSrc = $imageSrc;
                $retina3xWebpSrc = $webpSrc;

                // 2x Retina
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

                // 3x Retina für mobile
                if ($width <= 768 && ($retinaConfig = self::getRetinaConfig($config, $width, $originalWidth, 3))) {
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

                // Sizes und Sources generieren
                if ($breakpoint['maxWidth']) {
                    $sizes[] = '(max-width: ' . $breakpoint['maxWidth'] . 'px) ' . $width . 'px';
                } else {
                    $sizes[] = $width . 'px';
                }

                if ($breakpoint['maxWidth'] && !in_array($imageSrc, $processedSrcsets)) {
                    $mediaQuery = "(max-width: {$breakpoint['maxWidth']}px)";
                    if ($width <= 768) {
                        $sources[] = self::generateSource("image/webp", $webpSrc, $retinaWebpSrc, $retina3xWebpSrc, $mediaQuery);
                        $sources[] = self::generateSource("image/jpeg", $imageSrc, $retinaImageSrc, $retina3xImageSrc, $mediaQuery);
                    } else {
                        $sources[] = self::generateSource("image/webp", $webpSrc, $retinaWebpSrc, null, $mediaQuery);
                        $sources[] = self::generateSource("image/jpeg", $imageSrc, $retinaImageSrc, null, $mediaQuery);
                    }
                    $processedSrcsets[] = $imageSrc;
                } elseif (!$breakpoint['maxWidth']) {
                    $sources[] = self::generateSource("image/webp", $webpSrc, $retinaWebpSrc);
                    $sources[] = self::generateSource("image/jpeg", $imageSrc, $retinaImageSrc);
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
            $finalOutput .= '<div class="swiper-lazy-preloader"></div>';
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

    private static function handleSvg($baseImagePath, $rootDir, $altText, $meta, $headline, $size, $class): string
    {
        $imageSrc = str_replace($rootDir, '', $baseImagePath);
        $imageSrc = dirname($imageSrc) . '/' . rawurlencode(basename($imageSrc));

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
        if ($size && is_array($size) && ($size[0] != "" && $size[1] != "" && $size[2] != "")) {
            if ($size[0]) $config->setWidth((int)$size[0]);
            if ($size[1]) $config->setHeight((int)$size[1]);
            if ($size[2]) $config->setMode($size[2]);
        }

        try {
            $processedImage = $imageFactory->create(
                $rootDir . '/' . $imageObject->path,
                $config,
                self::getResizeOptions()
            );
            return str_replace($rootDir, "", $processedImage->getPath());
        } catch (\Exception $e) {
            return '';
        }
    }
}