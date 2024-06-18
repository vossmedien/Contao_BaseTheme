<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
use Contao\Image\ResizeConfiguration;


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
        $lazy = true
    )
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? System::getContainer()->getParameter('kernel.default_locale');

        if ($imageObject = FilesModel::findByUuid($imageSource)) {

            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = $imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [];
            $relativeImagePath = $imageObject->path;
        } else {
            $relativeImagePath = $imageSource;
            $meta = [];
        }

        $absoluteImagePath = $rootDir . '/' . urldecode($relativeImagePath);

        if (!file_exists($absoluteImagePath)) {
            echo "File does not exist: $absoluteImagePath\n";
            return '';
        }


        $baseImagePath = $absoluteImagePath;
        $baseImagePath = dirname($baseImagePath) . "/" . rawurlencode(basename($baseImagePath));

        if ($size && is_array($size)) {
            $width = isset($size[0]) ? (int)$size[0] : null;
            $height = isset($size[1]) ? (int)$size[1] : null;
            $mode = $size[2] ?? "proportional";

            $config = new ResizeConfiguration();

            if ($width !== "") {
                $config->setWidth($width);
            }
            if ($height !== "") {
                $config->setHeight($height);
            }

            if ($mode !== "") {
                $config->setMode($mode);
            }

            try {
                $baseImage = $imageFactory->create($absoluteImagePath, $config);
                $baseImagePath = $baseImage->getPath();
            } catch (\Exception $e) {
                //echo "Error creating base image: " . $e->getMessage();
                //echo "File: " . $e->getFile();
                //echo "Line: " . $e->getLine();
                //echo "Trace: " . $e->getTraceAsString();
                return '';
            }
        }


        $maxWidth = $size[0] ?? null;
        $breakpoints = [
            //['maxWidth' => 480, 'width' => 480],
            //['maxWidth' => 640, 'width' => 640],
            //['maxWidth' => 768, 'width' => 768],
            ['maxWidth' => 992, 'width' => 992],
            ['maxWidth' => 1200, 'width' => 1200],
            ['maxWidth' => 1600, 'width' => 1600],
            ['maxWidth' => 1920, 'width' => 1920],
            ['maxWidth' => null, 'width' => $maxWidth]
        ];

        $sources = [];
        $webpSources = [];
        $processedSrcsets = [];

        foreach ($breakpoints as $breakpoint) {
            $config = new ResizeConfiguration();
            $width = $breakpoint['width'];
            //$height = isset($size[1]) ? (int)$size[1] : null;
            $mode = $size[2] ?? "proportional";
            if ($maxWidth && $width > $maxWidth) {
                continue;
            }

            if ($width !== "") {
                $config->setWidth($width);
            }

            if ($mode !== "") {
                $config->setMode($mode);
            }

            try {
                $processedImage = $imageFactory->create($baseImagePath, $config);
                $processedImagePath = $processedImage->getPath();
                // Simulieren des Aufrufs des Bildes
                $imageUrl = str_replace($rootDir, '', $processedImagePath);
                $currentDomain = $_SERVER['HTTP_HOST'];
                $imageUrl = 'https://' . $currentDomain . $imageUrl;
                // Überprüfen, ob die Datei existiert
                if (!file_exists($processedImagePath)) {
                    $context = stream_context_create(['http' => ['timeout' => 0]]);
                    @file_get_contents($imageUrl, false, $context);
                    //throw new \Exception("File does not exist: $processedImagePath");
                }

                $imageSrc = str_replace($rootDir, '', $processedImagePath);
                $imageSrc = dirname($imageSrc) . '/' . rawurlencode(basename($imageSrc));

                // WebP-Version generieren
                $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $processedImagePath);
                if (!file_exists($webpPath)) {
                    $imagineImage = $processedImage->getImagine()->open($processedImagePath);
                    $imagineImage->save($webpPath, ['format' => 'webp']);
                }
                $webpSrc = str_replace($rootDir, '', $webpPath);
                $webpSrc = dirname($webpSrc) . '/' . rawurlencode(basename($webpSrc));

                if ($breakpoint['maxWidth']) {
                    if (!in_array($imageSrc, $processedSrcsets)) {
                        $mediaQuery = "(max-width: {$breakpoint['maxWidth']}px)";
                        $sources[] = "<source data-srcset=\"{$imageSrc}\" media=\"{$mediaQuery}\">";
                        $processedSrcsets[] = $imageSrc;
                    }

                    if (!in_array($webpSrc, $processedSrcsets)) {
                        $mediaQuery = "(max-width: {$breakpoint['maxWidth']}px)";
                        $webpSources[] = "<source data-srcset=\"{$webpSrc}\" media=\"{$mediaQuery}\" type=\"image/webp\">";
                        $processedSrcsets[] = $webpSrc;
                    }
                } elseif (!$breakpoint['maxWidth']) {
                    $sources[] = "<source data-srcset=\"{$imageSrc}\">";
                    $webpSources[] = "<source data-srcset=\"{$webpSrc}\" type=\"image/webp\">";
                }
            } catch (\Exception $e) {
                //echo "Error creating image: " . $e->getMessage();
                continue;
            }
        }

        if ($inSlider) {
            $classAttribute = $class ? ' class="' . htmlspecialchars($class) . ' "' : '';
        } else {
            $classAttribute = $class ? ' class="lazy ' . htmlspecialchars($class) . ' "' : 'class="lazy"';
        }
        $lazyAttribute = $lazy ? ' loading="lazy"' : '';


       $alt = !empty($meta['alt']) ? $meta['alt'] : (!empty($meta['title']) ? $meta['title'] : (!empty($altText) ? $altText : (!empty($headline) ? $headline : '')));
$title = !empty($meta['title']) ? $meta['title'] : (!empty($meta['caption']) ? $meta['caption'] : (!empty($headline) ? $headline : (!empty($meta['alt']) ? $meta['alt'] : (!empty($altText) ? $altText : ''))));
$link = !empty($meta['link']) ? $meta['link'] : '';
$caption = !empty($meta['caption']) ? $meta['caption'] : '';

        $linkStart = $linkEnd = '';
        if ($colorBox) {
            $linkStart = sprintf('<a title="%s" data-gall="group_%s" href="%s" class="lightbox_%s">',
                htmlspecialchars($title), htmlspecialchars($colorBox), $imageSrc, htmlspecialchars($colorBox));
            $linkEnd = '</a>';
        } elseif ($link) {
            $linkStart = sprintf('<a href="%s" title="%s">', htmlspecialchars($link), htmlspecialchars($title));
            $linkEnd = '</a>';
        }


        $imgTag = '<figure><picture>';

        $imgTag .= implode("\n", $webpSources);
        $imgTag .= implode("\n", $sources);

        if ($webpSources || $sources) {
            $imgTag .= '<img ' . $classAttribute . ' data-src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" alt="' . htmlspecialchars($alt) . '"' . $lazyAttribute . '>';
        } elseif ($imageSrc) {
            $imgTag .= '<img ' . $classAttribute . ' data-src="' . $imageSrc . '" alt="' . htmlspecialchars($alt) . '"' . $lazyAttribute . '>';
        }


        $imgTag .= '</picture>';

        if ($inSlider) {
            $imgTag .= '<div class="swiper-lazy-preloader"></div>';
            if ($caption) {
                $imgTag .= '<div class="slider-caption">';
                $imgTag .= htmlspecialchars($caption);
                $imgTag .= '</div>';
            }

            $imgTag = str_replace("data-src", "src", $imgTag);
            $imgTag = str_replace('loading="lazy"', '" ', $imgTag);
        } elseif ($caption) {
            if ($caption) {
                $imgTag .= '<figcaption>';
                $imgTag .= htmlspecialchars($caption);
                $imgTag .= '</figcaption>';
            }
        }
        $imgTag .= '</figure>';

        if ($linkStart || $linkEnd) {
            return $linkStart . $imgTag . $linkEnd;
        }
        return $imgTag;
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
