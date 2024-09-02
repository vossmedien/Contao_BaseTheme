<?php

namespace VSM_HelperFunctions;

use Contao\System;

class VideoHelper
{
    public static function renderVideo($src, $ext, $classes = '')
    {
        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $filePath = $rootDir . '/files/' . $src;
        $baseDir = dirname($filePath);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

        // Definiere die Reihenfolge der Formate nach Präferenz
        $formatOrder = ['webm', 'mp4', 'ogg'];

        $sources = [];
        foreach ($formatOrder as $format) {
            $potentialFile = $baseDir . '/' . $fileName . '.' . $format;
            if (file_exists($potentialFile)) {
                $sources[] = "<source type='video/$format' data-src='{{file::$src}}." . $format . "'>";
            }
        }

        // Wenn keine alternativen Formate gefunden wurden, verwende das ursprüngliche Format
        if (empty($sources)) {
            $sources[] = "<source type='video/$ext' data-src='{{file::$src}}'>";
        }

        $sourceString = implode("\n        ", $sources);

        return "<video class='lazy $classes' autoplay muted loop playsinline data-src='{{file::$src}}'>
        $sourceString
    </video>";
    }

    public static function isVideoFormat($extension)
    {
        $videoFormats = ['mp4', 'webm', 'ogg', 'mov']; // Fügen Sie hier weitere Videoformate hinzu, falls nötig
        return in_array(strtolower($extension), $videoFormats);
    }

}