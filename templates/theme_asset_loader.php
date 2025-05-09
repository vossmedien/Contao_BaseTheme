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

            // Stelle sicher, dass $assets['css'] initialisiert ist, falls es vorher noch nicht der Fall war
            if (!isset($assets['css'])) {
                $assets['css'] = [];
            }
            if (!isset($assets['fonts_preload'])) {
                $assets['fonts_preload'] = [];
            }

            foreach ($manifestData as $sourceName => $publicPath) {
                if (!is_string($publicPath) || empty($publicPath)) continue;

                // Der $sourceName aus dem Manifest ist jetzt der direkte Dateiname des Assets, z.B. "_vendors.bundle.min.css" oder "fonts/figtree-v7-latin-300.woff2"
                $currentFilenameForLogic = $sourceName;

                switch ($assetTypeForProcessing) {
                    case 'css_bundle': // Geändert von 'css' zu 'css_bundle' für die neue Logik
                        if (str_ends_with(strtolower($currentFilenameForLogic), '.css')) {
                            // Füge CSS-Datei hinzu, wenn sie nicht bereits vorhanden ist
                            if (!in_array(substr($publicPath, 1), $assets['css'])) {
                                $assets['css'][] = substr($publicPath, 1);
                            }
                        }
                        // Die Font-Verarbeitung für CSS-Bundles ist wichtig, da Fonts im CSS-Manifest auftauchen
                        else if (str_starts_with(strtolower($currentFilenameForLogic), 'fonts/') && preg_match('/\.(woff2|woff|ttf|otf|svg)$/i', $currentFilenameForLogic)) {
                            $fontType = match (strtolower(pathinfo($currentFilenameForLogic, PATHINFO_EXTENSION))) {
                                'woff2' => 'font/woff2',
                                'woff'  => 'font/woff',
                                'ttf'   => 'font/ttf',
                                'otf'   => 'font/otf',
                                'svg'   => 'image/svg+xml', // SVG als Font oder Bild
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
                            // Die Logik hier sollte den $themeNameClean aus der Webpack-Konfig entsprechen
                            // Für App-JS erwarten wir etwas wie 'caeliRelaunch.bundle.min.js'
                            if (str_contains($publicPath, $themeName . '.bundle.min.js') && !str_contains($publicPath, '_vendors.bundle.min.js')) {
                                if (!in_array(['src' => $publicPath, 'defer' => true, 'type' => 'module'], $assets['js_app'])) {
                                    $assets['js_app'][] = ['src' => $publicPath, 'defer' => true, 'type' => 'module'];
                                }
                            }
                        }
                        break;

                    case 'js_vendor':
                        if (str_ends_with(strtolower($currentFilenameForLogic), '.js')) {
                             // Für Vendor-JS erwarten wir etwas wie 'caeliRelaunch_vendors.bundle.min.js'
                            if (str_contains($publicPath, $themeName . '_vendors.bundle.min.js')) {
                                if (!in_array(['src' => $publicPath, 'defer' => true, 'type' => null], $assets['js_vendor'])) {
                                    $assets['js_vendor'][] = ['src' => $publicPath, 'defer' => true, 'type' => null];
                                }
                            }
                        }
                        break;
                }
            }
        };

        // Definierte Reihenfolge der CSS-Bundles
        $cssBundleOrder = ['_vendors', '_base', '_root-variables', '_theme', '_fonts'];

        foreach ($cssBundleOrder as $bundleName) {
            $processSingleManifestFile($themeManifestDir . '/' . $bundleName . '-css.manifest.json', 'css_bundle');
        }

        // JS-Manifeste wie gehabt verarbeiten
        $processSingleManifestFile($themeManifestDir . '/app-js.manifest.json', 'js_app');
        $processSingleManifestFile($themeManifestDir . '/vendor-js.manifest.json', 'js_vendor');
        
        if (empty($assets['css']) && empty($assets['js_app']) && empty($assets['js_vendor']) && empty($assets['fonts_preload'])) {
            // trigger_error("Keine Assets für Theme '{$themeName}' aus den Manifesten unter '{$themeManifestDir}' geladen. Bitte Webpack-Build und Manifest-Pfade prüfen.", E_USER_WARNING);
        }
        // var_dump($assets); 
        return $assets;
    }
} 