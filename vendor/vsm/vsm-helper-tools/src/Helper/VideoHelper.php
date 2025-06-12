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

use Contao\FilesModel;
use Contao\System;
use Contao\StringUtil;

class VideoHelper
{
    private const VIDEO_FORMATS = ['webm', 'mp4'];
    private const DEFAULT_VIDEO_PARAMS = 'autoplay muted loop playsinline';

    public static function renderVideo(
        string $source,
        string $classes = '',
        ?string $name = null,
        ?string $description = null,
        ?string $uploadDate = null,
        ?string $posterUrl = null,
        string $videoParams = '',
        bool $lazy = true
    ): string {
        $isUrl = filter_var($source, FILTER_VALIDATE_URL) !== false;
        $sources = [];
        $mp4Path = '';
        $posterPath = '';
        
        if ($isUrl) {
            return self::handleExternalUrl($source, $lazy, $sources, $mp4Path);
        }
        
        $fileModel = FilesModel::findByUuid($source);
        if ($fileModel === null) {
            return '';
        }

        $rootDir = System::getContainer()->getParameter('kernel.project_dir');
        $filePath = $rootDir . '/' . $fileModel->path;
        $baseDir = dirname($filePath);
        $fileName = pathinfo($filePath, PATHINFO_FILENAME);

        // Video-Sources generieren
        self::generateVideoSources($baseDir, $fileName, $rootDir, $lazy, $sources, $mp4Path);
        
        if (empty($sources)) {
            return '';
        }

        // Poster-Bild suchen wenn nicht bereitgestellt
        if ($posterUrl === null) {
            $posterPath = self::findPosterImage($baseDir, $fileName, $rootDir);
        }

        // Metadaten für Structured Data
        $langMeta = self::getLanguageMetadata($fileModel);
        
        return self::buildVideoOutput(
            $sources,
            $classes,
            $videoParams,
            $posterUrl,
            $posterPath,
            $mp4Path,
            $lazy,
            $name,
            $description,
            $uploadDate,
            $langMeta,
            $fileModel,
            $isUrl
        );
    }

    private static function handleExternalUrl(string $source, bool $lazy, array &$sources, string &$mp4Path): string
    {
        $ext = pathinfo(parse_url($source, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!self::isVideoFormat($ext)) {
            return '';
        }
        
        $sources[] = sprintf(
            "<source %s='%s' type='video/%s'>",
            $lazy ? 'data-src' : 'src',
            $source,
            $ext
        );
        $mp4Path = $source;
        
        // Für externe URLs direkt das Video ausgeben
        return self::buildVideoOutput(
            $sources,
            '',
            '',
            null,
            '',
            $mp4Path,
            $lazy,
            null,
            null,
            null,
            [],
            null,
            true
        );
    }

    private static function generateVideoSources(
        string $baseDir,
        string $fileName,
        string $rootDir,
        bool $lazy,
        array &$sources,
        string &$mp4Path
    ): void {
        foreach (self::VIDEO_FORMATS as $format) {
            $potentialFile = $baseDir . '/' . $fileName . '.' . $format;
            if (!file_exists($potentialFile)) {
                continue;
            }
            
            $relativePath = str_replace($rootDir . '/', '', $potentialFile);
            $potentialFileModel = FilesModel::findByPath($relativePath);
            
            if ($potentialFileModel !== null) {
                $sources[] = sprintf(
                    "<source %s='%s' type='video/%s'>",
                    $lazy ? 'data-src' : 'src',
                    $potentialFileModel->path,
                    $format
                );
                
                if ($format === 'mp4' && empty($mp4Path)) {
                    $mp4Path = $potentialFileModel->path;
                }
            }
        }
    }

    private static function findPosterImage(string $baseDir, string $fileName, string $rootDir): string
    {
        $posterFile = $baseDir . '/' . $fileName . '.jpg';
        if (!file_exists($posterFile)) {
            return '';
        }
        
        $relativePath = str_replace($rootDir . '/', '', $posterFile);
        $posterFileModel = FilesModel::findByPath($relativePath);
        
        return $posterFileModel?->path ?? '';
    }

    private static function getLanguageMetadata(?FilesModel $fileModel): array
    {
        if ($fileModel === null) {
            return [];
        }
        
        $currentLanguage = $GLOBALS['TL_LANGUAGE'] ?? 'de';
        $meta = StringUtil::deserialize($fileModel->meta) ?? [];
        
        return $meta[$currentLanguage] ?? [];
    }

    private static function buildVideoOutput(
        array $sources,
        string $classes,
        string $videoParams,
        ?string $posterUrl,
        string $posterPath,
        string $mp4Path,
        bool $lazy,
        ?string $name,
        ?string $description,
        ?string $uploadDate,
        array $langMeta,
        ?FilesModel $fileModel,
        bool $isUrl
    ): string {
        $sourceString = implode("\n        ", $sources);
        
        // Video-Parameter
        $videoParams = trim($videoParams) ?: self::DEFAULT_VIDEO_PARAMS;
        
        // Structured Data
        $structuredData = self::buildStructuredData(
            $name,
            $description,
            $uploadDate,
            $langMeta,
            $fileModel,
            $posterUrl,
            $posterPath,
            $mp4Path,
            $isUrl
        );
        
        // Poster-Attribut
        $posterSrc = $posterUrl ?: $posterPath;
        $posterAttr = $posterSrc ? 
            sprintf(" %s='%s'", $lazy ? 'data-poster' : 'poster', $posterSrc) : '';
        
        // Video-HTML generieren
        $videoClass = trim($classes . ($lazy ? ' lazy' : ''));
        $srcAttr = !$lazy && $mp4Path ? " src='$mp4Path'" : '';
        
        $videoHtml = sprintf(
            "<div class='content-media'>\n    <video class='%s' %s%s preload='none'%s>\n        %s\n        <p>Your browser does not support HTML5 video. Here is a <a href='%s'>link to the video</a> instead.</p>\n    </video>\n</div>",
            $videoClass,
            $videoParams,
            $posterAttr,
            $srcAttr,
            $sourceString,
            $mp4Path
        );

        // Lazy-Loading Event
        if ($lazy) {
            $videoHtml .= "\n<script>
                document.dispatchEvent(new CustomEvent('vsm:videoLoaded', {
                    detail: {
                        videoElement: document.currentScript.previousElementSibling.querySelector('video')
                    }
                }));
            </script>";
        }

        $structuredDataScript = $structuredData ? 
            '<script type="application/ld+json">' . json_encode($structuredData) . '</script>' : '';
        
        return $structuredDataScript . $videoHtml;
    }

    private static function buildStructuredData(
        ?string $name,
        ?string $description,
        ?string $uploadDate,
        array $langMeta,
        ?FilesModel $fileModel,
        ?string $posterUrl,
        string $posterPath,
        string $mp4Path,
        bool $isUrl
    ): ?array {
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject'
        ];

        $title = $name ?? $langMeta['title'] ?? null;
        if ($title) {
            $structuredData['name'] = strip_tags($title);
        }

        $desc = $description ?? $langMeta['description'] ?? null;
        if ($desc) {
            $structuredData['description'] = strip_tags($desc);
        }

        $date = $uploadDate ?? (!$isUrl && $fileModel?->tstamp ? date('c', $fileModel->tstamp) : null);
        if ($date) {
            $structuredData['uploadDate'] = $date;
        }

        $host = 'https://' . ($_SERVER['HTTP_HOST'] ?? '');
        
        $posterSrc = $posterUrl ?: $posterPath;
        if ($posterSrc) {
            $structuredData['thumbnailUrl'] = $host . '/' . ltrim($posterSrc, '/');
        }

        if ($mp4Path) {
            $structuredData['contentUrl'] = $host . '/' . ltrim($mp4Path, '/');
        }

        // Nur zurückgeben wenn mindestens ein zusätzliches Feld gesetzt ist
        return count($structuredData) > 2 ? $structuredData : null;
    }

    public static function isVideoFormat(string $extension): bool
    {
        return in_array(strtolower($extension), self::VIDEO_FORMATS, true);
    }
}