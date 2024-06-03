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
        $imageObject = FilesModel::findByUuid($imageSource);
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $globalLanguage = System::getContainer()->getParameter('kernel.default_locale');
        $imageFactory = System::getContainer()->get('contao.image.factory');
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? $globalLanguage;


        if ($imageObject) {
            $imageMeta = StringUtil::deserialize($imageObject->meta, true);
            $meta = $imageMeta[$currentLanguage] ?? reset($imageMeta) ?? [];
            $relativeImagePath = $imageObject->path;
            $absoluteImagePath = $rootDir . '/' . $relativeImagePath;
        } else {
            $imageObject = $imageSource;
            $relativeImagePath = $imageObject;
            $absoluteImagePath = $rootDir . '' . $relativeImagePath;
        }

        if (!file_exists($absoluteImagePath)) {
            echo "Fehler: Das Bild '$absoluteImagePath' existiert nicht.";
            return '';
        }

        $config = new ResizeConfiguration();
        $originalWidth = 0;
        $originalHeight = 0;

        try {
            $imageDimensions = getimagesize($absoluteImagePath);
            $originalWidth = $imageDimensions[0];
            $originalHeight = $imageDimensions[1];

            if ($size && is_array($size) && ($size[0] != "" && $size[1] != "" && $size[2] != "")) {
                $width = isset($size[0]) ? (int)$size[0] : null;
                $height = isset($size[1]) ? (int)$size[1] : null;
                $mode = $size[2] ?? null;

                if ($width !== null) {
                    $config->setWidth($width);
                }
                if ($height !== null) {
                    $config->setHeight($height);
                }
                if ($mode !== null) {
                    $config->setMode($mode);
                }

                $processedImage = $imageFactory->create($absoluteImagePath, $config);
                $imageSrc = $processedImage->getPath();
                $imageSrc = str_replace($rootDir, "", $imageSrc);

                $resizeWidth = $width ?? $originalWidth;
            } else {
                $processedImage = $imageFactory->create($absoluteImagePath, $config);
                $imageSrc = $processedImage->getPath();
                $imageSrc = str_replace($rootDir, "", $imageSrc);

                $resizeWidth = $originalWidth;
            }
        } catch (\Exception $e) {
            echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
            return '';
        }

        $alt = '';
        if (is_array($meta)) {
            $alt = $meta['alt'] ?? $meta['title'] ?? '';
        }
        if (empty($alt) && is_string($altText)) {
            $alt = $altText;
        }
        if (empty($alt) && is_string($headline)) {
            $alt = $headline;
        }

        $title = '';
        if (is_string($headline)) {
            $title = $headline;
        } elseif (is_array($meta)) {
            $title = $meta['title'] ?? $meta['alt'] ?? '';
        }
        if (empty($title) && is_string($altText)) {
            $title = $altText;
        }

        $link = is_array($meta) ? ($meta['link'] ?? '') : '';
        $caption = is_array($meta) ? ($meta['caption'] ?? '') : '';


        if (!empty($colorBox)) {
            $linkStart = '<a title="' . htmlspecialchars($title) . '" data-gall="group_' . htmlspecialchars($colorBox) . '" href="' . htmlspecialchars($relativeImagePath) . '" class="lightbox_' . htmlspecialchars($colorBox) . '">';
            $linkEnd = '</a>';
        } else {
            if (!empty($link)) {
                $linkStart = '<a href="' . htmlspecialchars($link) . '" title="' . htmlspecialchars($title) . '">';
                $linkEnd = '</a>';
            } else {
                $linkStart = '';
                $linkEnd = '';
            }
        }

        // Funktion zum Bereinigen des Dateinamens
        $sanitizeFileName = function ($filename) {
            // Ersetze Umlaute
            $umlaute = ['ä', 'ö', 'ü', 'ß', 'Ä', 'Ö', 'Ü'];
            $ersetze = ['ae', 'oe', 'ue', 'ss', 'Ae', 'Oe', 'Ue'];
            $filename = str_replace($umlaute, $ersetze, $filename);

            // Ersetze Sonderzeichen
            $filename = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $filename);

            // Konvertiere Leerzeichen zu Unterstrichen
            $filename = str_replace(' ', '_', $filename);

            return $filename;
        };

        // Bildgrößen für das <picture>-Tag und WebP-Version
        $srcSetParts = [];
        $webpSrcSetParts = [];
        $breakpoints = [480, 768, 992, 1200, 1600, 1920];


        if ($size && is_array($size) && ($size[0] != "" && $size[1] != "" && $size[2] != "") && $size[0] < min($breakpoints)) {
            try {
                $config->setWidth($size[0])->setHeight($size[1])->setMode($size[2]);
                $processedImage = $imageFactory->create($absoluteImagePath, $config);
                $processedImagePath = $processedImage->getPath();

                // Dateipfad und -name des generierten Bildes bereinigen
                $pathParts = pathinfo($processedImagePath);
                $sanitizedFilename = $sanitizeFileName($pathParts['basename']);
                $sanitizedProcessedImagePath = $pathParts['dirname'] . '/' . $sanitizedFilename;

                // Sicherstellen, dass das bereinigte Bild erstellt wird
                if (!file_exists($sanitizedProcessedImagePath)) {
                    copy($processedImagePath, $sanitizedProcessedImagePath);
                }

                // Relativen Pfad verwenden
                $relativeSanitizedProcessedImagePath = str_replace($rootDir, '', $sanitizedProcessedImagePath);

                // Klassennamen und Loading-Attribut hinzufügen
                $classAttribute = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
                $loadingAttribute = $lazy ? ' loading="lazy"' : '';

                // Ausgabe des Bildes ohne <picture>-Tag oder srcset
                return $linkStart . '<img' . $classAttribute . ' src="' . htmlspecialchars($relativeSanitizedProcessedImagePath) . '" alt="' . htmlspecialchars($alt) . '"' . $loadingAttribute . '>' . $linkEnd;
            } catch (\Exception $e) {
                //echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
            }
        }

        foreach ($breakpoints as $bp) {
            // Entscheiden, ob die Breakpoints anhand der Originalbildgröße oder der übergebenen $size-Variablen verwendet werden
            if ($size && is_array($size) && ($size[0] != "" && $size[1] != "" && $size[2] != "")) {
                if ($bp <= $size[0]) {
                    try {
                        $config->setWidth($bp);
                        $processedImage = $imageFactory->create($absoluteImagePath, $config);
                        $processedImagePath = $processedImage->getPath();

                        // Dateipfad und -name des generierten Bildes bereinigen
                        $pathParts = pathinfo($processedImagePath);
                        $sanitizedFilename = $sanitizeFileName($pathParts['basename']);
                        $sanitizedProcessedImagePath = $pathParts['dirname'] . '/' . $sanitizedFilename;

                        // Sicherstellen, dass das bereinigte Bild erstellt wird
                        if (!file_exists($sanitizedProcessedImagePath)) {
                            copy($processedImagePath, $sanitizedProcessedImagePath);
                        }

                        // Relativen Pfad für srcset verwenden
                        $relativeSanitizedProcessedImagePath = str_replace($rootDir, '', $sanitizedProcessedImagePath);
                        $srcSetParts[] = $relativeSanitizedProcessedImagePath . ' ' . $bp . 'w';

                        // WebP-Version generieren
                        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $sanitizedProcessedImagePath);
                        if (!file_exists($webpPath)) {
                            $imagineImage = $processedImage->getImagine()->open($sanitizedProcessedImagePath);
                            $imagineImage->save($webpPath, ['format' => 'webp']);
                        }               // Relativen Pfad für WebP srcset verwenden
                        $relativeWebpPath = str_replace($rootDir, '', $webpPath);
                        $webpSrcSetParts[] = $relativeWebpPath . ' ' . $bp . 'w';
                    } catch (\Exception $e) {
                        //echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
                    }
                }
            } else {
                if ($bp <= $originalWidth) {
                    try {
                        $config->setWidth($bp);
                        $processedImage = $imageFactory->create($absoluteImagePath, $config);
                        $processedImagePath = $processedImage->getPath();

                        // Dateipfad und -name des generierten Bildes bereinigen
                        $pathParts = pathinfo($processedImagePath);
                        $sanitizedFilename = $sanitizeFileName($pathParts['basename']);
                        $sanitizedProcessedImagePath = $pathParts['dirname'] . '/' . $sanitizedFilename;

                        // Sicherstellen, dass das bereinigte Bild erstellt wird
                        if (!file_exists($sanitizedProcessedImagePath)) {
                            copy($processedImagePath, $sanitizedProcessedImagePath);
                        }

                        // Relativen Pfad für srcset verwenden
                        $relativeSanitizedProcessedImagePath = str_replace($rootDir, '', $sanitizedProcessedImagePath);
                        $srcSetParts[] = $relativeSanitizedProcessedImagePath . ' ' . $bp . 'w';

                        // WebP-Version generieren
                        $webpPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.webp', $sanitizedProcessedImagePath);
                        if (!file_exists($webpPath)) {
                            $imagineImage = $processedImage->getImagine()->open($sanitizedProcessedImagePath);
                            $imagineImage->save($webpPath, ['format' => 'webp']);
                        }

                        // Relativen Pfad für WebP srcset verwenden
                        $relativeWebpPath = str_replace($rootDir, '', $webpPath);
                        $webpSrcSetParts[] = $relativeWebpPath . ' ' . $bp . 'w';
                    } catch (\Exception $e) {
                        //echo "Fehler beim Bearbeiten des Bildes: " . $e->getMessage();
                    }
                }
            }
        }

        $srcSet = implode(', ', array_map(fn($path) => htmlspecialchars($path), $srcSetParts));
        $webpSrcSet = implode(', ', array_map(fn($path) => htmlspecialchars($path), $webpSrcSetParts));

        // Quelldateipfad bereinigen
        $sanitizedImageSrc = str_replace($rootDir, '', $absoluteImagePath);
        $sanitizedImageSrc = dirname($sanitizedImageSrc) . '/' . $sanitizeFileName(basename($sanitizedImageSrc));

        // Generierung des Bild-HTML mit <picture>-Tag
        $classAttribute = $class ? ' class="' . htmlspecialchars($class) . '"' : '';
        $pictureTag = '<picture>';
        if ($webpSrcSet) {
            $pictureTag .= '<source type="image/webp" srcset="' . $webpSrcSet . ', ' . htmlspecialchars($sanitizedImageSrc) . ' ' . $originalWidth . 'w" sizes="(max-width: 480px) 480px, 100vw">';
        }
        if ($srcSet) {
            $pictureTag .= '<source srcset="' . $srcSet . ', ' . htmlspecialchars($sanitizedImageSrc) . ' ' . $originalWidth . 'w" sizes="(max-width: 480px) 480px, 100vw">';
        }
        $pictureTag .= '<img' . $classAttribute . ' src="' . htmlspecialchars($sanitizedImageSrc) . '" alt="' . htmlspecialchars($alt) . '"' . ($lazy ? ' loading="lazy"' : '') . '>';
        // Swiper-Preloader hinzufügen, wenn $inSlider true ist
        if ($inSlider) {
            $pictureTag .= '<div class="swiper-lazy-preloader"></div>';
        }

        $pictureTag .= '</picture>';

        // Ausgabe des vollständigen HTML
        return $linkStart . $pictureTag . $linkEnd;
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
            $mode = $size[2] ?? null;

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
