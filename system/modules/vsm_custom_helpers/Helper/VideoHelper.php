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
            // If source is a URL, use it directly and try to determine the extension
            $ext = pathinfo(parse_url($source, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (self::isVideoFormat($ext)) {
                $sources[] = "<source src='$source' type='video/$ext'>";
                $mp4Path = $source;
            } else {
                return ''; // Invalid video format or unable to determine format
            }
        } else {
            // If source is a fileID, process it as before
            $fileModel = FilesModel::findByUuid($source);
            if ($fileModel === null) {
                return ''; // File not found
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

            // Search for a poster image if not provided
            if ($posterUrl === null) {
                $posterFile = $baseDir . '/' . $fileName . '.jpg'; // or .png
                if (file_exists($posterFile)) {
                    $posterFileModel = FilesModel::findByPath(str_replace($rootDir . '/', '', $posterFile));
                    if ($posterFileModel !== null) {
                        $posterPath = $posterFileModel->path;
                    }
                }
            }
        }

        if (empty($sources)) {
            return ''; // No valid video sources found
        }

        $sourceString = implode("\n        ", $sources);
        $posterAttr = $posterUrl ? " poster='$posterUrl'" : ($posterPath ? " poster='$posterPath'" : '');

        // Set video parameters
        $videoParams = trim($videoParams);
        if (empty($videoParams)) {
            $videoParams = 'autoplay muted loop playsinline';
        }

        // Retrieve metadata
        $meta = [];
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? 'de';
        if (!$isUrl) {
            $meta = StringUtil::deserialize($fileModel->meta);
            $langMeta = $meta[$currentLanguage] ?? [];
        }

        // Prepare data for structured data
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

        // Create structured data if at least one field is present
        $structuredDataScript = '';
        if (count($structuredData) > 2) { // More than @context and @type
            $structuredDataScript = '<script type="application/ld+json">' . json_encode($structuredData) . '</script>';
        }

        $lazyClass = $isUrl ? '' : ' lazy';
        $videoHtml = "<video class='$classes$lazyClass' $videoParams$posterAttr>
        $sourceString
        <p>Your browser does not support HTML5 video. Here is a <a href='$mp4Path'>link to the video</a> instead.</p>
    </video>";

        return $structuredDataScript . $videoHtml;
    }

    public static function isVideoFormat($extension)
    {
        $videoFormats = ['mp4', 'webm', 'ogg', 'mov']; // Add more video formats here if needed
        return in_array(strtolower($extension), $videoFormats);
    }
}