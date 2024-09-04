<?php

namespace VSM_HelperFunctions;

use Contao\FilesModel;
use Contao\System;

class VideoHelper
{
    public static function renderVideo($fileId, $ext, $classes = '')
    {
        // Holen Sie sich das FilesModel-Objekt anhand der ID
        $fileModel = FilesModel::findByUuid($fileId);

        if ($fileModel === null) {
            return ''; // Datei nicht gefunden
        }

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $filePath = $rootDir . '/' . $fileModel->path;
        $baseDir = dirname($filePath);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

        // Definiere die Reihenfolge der Formate nach Präferenz
        $formatOrder = ['webm', 'mp4', 'ogg'];

        $sources = [];
        foreach ($formatOrder as $format) {
            $potentialFile = $baseDir . '/' . $fileName . '.' . $format;
            if (file_exists($potentialFile)) {
                // Suche nach dem FilesModel für diese Datei
                $potentialFileModel = FilesModel::findByPath(str_replace($rootDir . '/', '', $potentialFile));
                if ($potentialFileModel !== null) {
                    $sources[] = "<source type='video/$format' src='" . $potentialFileModel->path . "'>";
                }
            }
        }

        // Wenn keine alternativen Formate gefunden wurden, verwende das ursprüngliche Format
        if (empty($sources)) {
            $sources[] = "<source type='video/$ext' src='" . $fileModel->path . "'>";
        }

        $sourceString = implode("\n        ", $sources);

        // Verwende das erste gefundene Format für das data-src Attribut des Video-Tags
        $firstSource = reset($sources);
        preg_match('/src=\'([^\']+)\'/', $firstSource, $matches);
        $dataSrc = $matches[1] ?? $fileModel->path;

        return "<video class='lazy $classes' autoplay muted loop playsinline data-src='$dataSrc'>
        $sourceString
    </video>";
    }

    public static function isVideoFormat($extension)
    {
        $videoFormats = ['mp4', 'webm', 'ogg', 'mov']; // Fügen Sie hier weitere Videoformate hinzu, falls nötig
        return in_array(strtolower($extension), $videoFormats);
    }
}