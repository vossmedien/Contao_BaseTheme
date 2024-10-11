<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;
class VideoHelper
{
public static function renderVideo($fileId, $ext, $classes = '', $name = null, $description = null, $uploadDate = null)
{
    $fileModel = FilesModel::findByUuid($fileId);
    if ($fileModel === null) {
        return ''; // Datei nicht gefunden
    }

    $rootDir = System::getContainer()->getParameter('kernel.project_dir');
    $filePath = $rootDir . '/' . $fileModel->path;
    $baseDir = dirname($filePath);
    $fileName = pathinfo($filePath, PATHINFO_FILENAME);

    $formatOrder = ['webm', 'mp4', 'ogg'];

    $sources = [];
    $posterPath = '';
    $mp4Path = '';

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

    // Suche nach einem Poster-Bild
    $posterFile = $baseDir . '/' . $fileName . '.jpg'; // oder .png
    if (file_exists($posterFile)) {
        $posterFileModel = FilesModel::findByPath(str_replace($rootDir . '/', '', $posterFile));
        if ($posterFileModel !== null) {
            $posterPath = $posterFileModel->path;
        }
    }

    $sourceString = implode("\n        ", $sources);
    $posterAttr = $posterPath ? " data-poster='$posterPath'" : '';

    // Metadaten abrufen
    $meta = StringUtil::deserialize($fileModel->meta);
    $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? 'de';
    $langMeta = $meta[$currentLanguage] ?? [];

    // Daten für strukturierte Daten vorbereiten
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

    if ($uploadDate ?? ($fileModel->tstamp ? date('c', $fileModel->tstamp) : null)) {
        $structuredData['uploadDate'] = $uploadDate ?? date('c', $fileModel->tstamp);
    }

    if ($posterPath) {
        $structuredData['thumbnailUrl'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $posterPath;
    }

    if ($mp4Path) {
        $structuredData['contentUrl'] = 'https://' . $_SERVER['HTTP_HOST'] . '/' . $mp4Path;
    }

    // Strukturierte Daten erstellen, wenn mindestens ein Feld vorhanden ist
    $structuredDataScript = '';
    if (count($structuredData) > 2) { // Mehr als @context und @type
        $structuredDataScript = '<script type="application/ld+json">' . json_encode($structuredData) . '</script>';
    }

    $videoHtml = "<video class='lazy $classes' autoplay muted loop playsinline$posterAttr>
        $sourceString
        <p>Ihr Browser unterstützt kein HTML5-Video. Hier ist stattdessen ein <a href='$mp4Path'>Link zum Video</a>.</p>
    </video>";

    return $structuredDataScript . $videoHtml;
}


    public static function isVideoFormat($extension)
    {
        $videoFormats = ['mp4', 'webm', 'ogg', 'mov']; // Fügen Sie hier weitere Videoformate hinzu, falls nötig
        return in_array(strtolower($extension), $videoFormats);
    }
}