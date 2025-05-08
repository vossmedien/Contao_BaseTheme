<?php

// --- Hilfsfunktionen für Theme-Assets ---

if (!function_exists('get_theme_name_from_dir')) {
    function get_theme_name_from_dir(string $dir): string {
        // Extrahiert den letzten Teil des Verzeichnispfades
        return basename($dir) ?: 'default';
    }
}

if (!function_exists('load_theme_assets_from_manifest')) {
    function load_theme_assets_from_manifest(string $themeName, string $templateDir): array {
        $projectRoot = dirname($templateDir, 2);
        $themeManifestParentDir = $projectRoot . '/files/base/layout/_vendor/_dist-manifest/';
        $themeManifestDir = $themeManifestParentDir . '_' . $themeName;

        $assets = ['css' => [], 'js_vendor' => [], 'js_app' => [], 'fonts_preload' => []];

        $processSingleManifestFile = function(string $manifestFilePath, string $assetTypeForProcessing) use (&$assets, $themeName) {
            // error_log("Theme '{$themeName}': Trying to process manifest '{$manifestFilePath}'. Exists: " . (file_exists($manifestFilePath) ? 'Yes' : 'No'));
            if (!file_exists($manifestFilePath)) {
                return;
            }

            $manifestJson = file_get_contents($manifestFilePath);
            if ($manifestJson === false) {
                trigger_error("Konnte Manifest-Datei '{$manifestFilePath}' für Theme '{$themeName}' nicht lesen.", E_USER_WARNING);
                return;
            }

            $manifestData = json_decode($manifestJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                trigger_error("Fehler beim Parsen von Manifest '{$manifestFilePath}' für Theme '{$themeName}': " . json_last_error_msg(), E_USER_WARNING);
                return;
            }

            if (!is_array($manifestData)) {
                trigger_error("Manifest-Daten in '{$manifestFilePath}' sind unerwarteterweise kein Array/Objekt.", E_USER_WARNING);
                return;
            }

            foreach ($manifestData as $sourceName => $publicPath) {
                if (!is_string($publicPath) || empty($publicPath)) continue;

                $currentFilenameForLogic = $sourceName;

                switch ($assetTypeForProcessing) {
                    case 'css':
                        if (str_ends_with(strtolower($currentFilenameForLogic), '.css')) {
                            $assets['css'][] = substr($publicPath, 1);
                        }
                        else if (str_starts_with(strtolower($currentFilenameForLogic), 'fonts/') && preg_match('/\.(woff2|woff|ttf|otf)$/i', $currentFilenameForLogic)) {
                            $fontType = match (strtolower(pathinfo($currentFilenameForLogic, PATHINFO_EXTENSION))) {
                                'woff2' => 'font/woff2',
                                'woff'  => 'font/woff',
                                'ttf'   => 'font/ttf',
                                'otf'   => 'font/otf',
                                default => '',
                            };
                            if ($fontType) {
                                $found = false;
                                foreach ($assets['fonts_preload'] as $existingFont) {
                                    if ($existingFont['href'] === $publicPath) {
                                        $found = true;
                                        break;
                                    }
                                }
                                if (!$found) {
                                    $assets['fonts_preload'][] = ['href' => $publicPath, 'type' => $fontType];
                                }
                            }
                        }
                        break;

                    case 'js_app':
                        if (str_ends_with(strtolower($currentFilenameForLogic), '.js')) {
                            if (str_contains($publicPath, $themeName . '.bundle.min.js') && !str_contains($publicPath, '_vendors.bundle.min.js')) {
                                $assets['js_app'][] = ['src' => $publicPath, 'defer' => true, 'type' => 'module'];
                            }
                        }
                        break;

                    case 'js_vendor':
                        if (str_ends_with(strtolower($currentFilenameForLogic), '.js')) {
                            if (str_contains($publicPath, $themeName . '_vendors.bundle.min.js')) {
                                $assets['js_vendor'][] = ['src' => $publicPath, 'defer' => true, 'type' => null];
                            }
                        }
                        break;
                }
            }
        };

        $processSingleManifestFile($themeManifestDir . '/css.manifest.json', 'css');
        $processSingleManifestFile($themeManifestDir . '/app-js.manifest.json', 'js_app');
        $processSingleManifestFile($themeManifestDir . '/vendor-js.manifest.json', 'js_vendor');
        
        if (empty($assets['css']) && empty($assets['js_app']) && empty($assets['js_vendor']) && empty($assets['fonts_preload'])) {
            // trigger_error("Keine Assets für Theme '{$themeName}' aus den Manifesten unter '{$themeManifestDir}' geladen. Bitte Webpack-Build und Manifest-Pfade prüfen.", E_USER_WARNING);
        }
        // var_dump($assets); 
        return $assets;
    }
} 