<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;

class VideoHelper
{
    public static function renderVideo($source, $classes = '', $name = null, $description = null, $uploadDate = null, $posterUrl = null, $videoParams = '')
    {
        $isUrl = filter_var($source, FILTER_VALIDATE_URL) !== false;
        $sources = [];
        $mp4Path = '';
        $posterPath = '';
        $ext = null;

        if ($isUrl) {
            // Für externe URLs
            $ext = pathinfo(parse_url($source, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (self::isVideoFormat($ext)) {
                $sources[] = "<source data-src='$source' type='video/$ext'>";
                $mp4Path = $source;
            } else {
                return '';
            }
        } else {
            // Für lokale Dateien
            $fileModel = FilesModel::findByUuid($source);
            if ($fileModel === null) {
                return '';
            }

            $rootDir = System::getContainer()->getParameter('kernel.project_dir');
            $filePath = $rootDir . '/' . $fileModel->path;
            $baseDir = dirname($filePath);
            $fileName = pathinfo($filePath, PATHINFO_FILENAME);

            $formatOrder = ['webm', 'mp4', 'ogg'];

            foreach ($formatOrder as $format) {
                $potentialFile = $baseDir . '/' . $fileName . '.' . $format;
                if (file_exists($potentialFile)) {
                    $potentialFileModel = FilesModel::findByPath(str_replace($rootDir . '/', '', $potentialFile));
                    if ($potentialFileModel !== null) {
                        $sources[] = "<source data-src='" . $potentialFileModel->path . "' type='video/$format'>";
                        if ($format === 'mp4' && empty($mp4Path)) {
                            $mp4Path = $potentialFileModel->path;
                        }
                    }
                }
            }

            // Poster-Bild suchen wenn nicht bereitgestellt
            if ($posterUrl === null) {
                $posterFile = $baseDir . '/' . $fileName . '.jpg';
                if (file_exists($posterFile)) {
                    $posterFileModel = FilesModel::findByPath(str_replace($rootDir . '/', '', $posterFile));
                    if ($posterFileModel !== null) {
                        $posterPath = $posterFileModel->path;
                    }
                }
            }
        }

        if (empty($sources)) {
            return '';
        }

        $sourceString = implode("\n        ", $sources);

        // Poster-URL für Lazy Loading vorbereiten
        $posterAttr = '';
        if ($posterUrl) {
            $posterAttr = " data-poster='$posterUrl'";
        } elseif ($posterPath) {
            $posterAttr = " data-poster='$posterPath'";
        }

        // Video-Parameter
        $videoParams = trim($videoParams);
        if (empty($videoParams)) {
            $videoParams = 'autoplay muted loop playsinline';
        }

        // Metadaten
        $meta = [];
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? 'de';
        if (!$isUrl) {
            $meta = StringUtil::deserialize($fileModel->meta);
            $langMeta = $meta[$currentLanguage] ?? [];
        }

        // Structured Data vorbereiten
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject'
        ];

        if ($name ?? $langMeta['title'] ?? null) {
            $structuredData['name'] = strip_tags($name ?? $langMeta['title']);
        }

        if ($description ?? $langMeta['description'] ?? null) {
            $structuredData['description'] = strip_tags($description ?? $langMeta['description']);
        }

        if ($uploadDate ?? (!$isUrl && $fileModel->tstamp ? date('c', $fileModel->tstamp) : null)) {
            $structuredData['uploadDate'] = $uploadDate ?? (!$isUrl ? date('c', $fileModel->tstamp) : null);
        }

        if ($posterUrl || $posterPath) {
            $structuredData['thumbnailUrl'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . ($posterUrl ?: $posterPath);
        }

        if ($mp4Path) {
            $structuredData['contentUrl'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $mp4Path;
        }

        $structuredDataScript = '';
        if (count($structuredData) > 2) {
            $structuredDataScript = '<script type="application/ld+json">' . json_encode($structuredData) . '</script>';
        }

        // Video-HTML mit Lazy Loading
        $videoHtml = "<video class='$classes lazy' $videoParams$posterAttr preload='none'>
        $sourceString
        <p>Your browser does not support HTML5 video. Here is a <a href='$mp4Path'>link to the video</a> instead.</p>
    </video>";

        return $structuredDataScript . $videoHtml;
    }

    public static function isVideoFormat($extension)
    {
        $videoFormats = ['mp4', 'webm', 'ogg', 'mov'];
        return in_array(strtolower($extension), $videoFormats);
    }
}