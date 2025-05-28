<?php

// --- Hilfsfunktionen für Theme-Assets ---

if (!function_exists('get_theme_name_from_dir')) {
    function get_theme_name_from_dir(string $dir): string {
        // Extrahiert den letzten Teil des Verzeichnispfades
        return basename($dir) ?: 'default';
    }
}

if (!function_exists('load_theme_assets_from_manifest')) {
    // Parameter umbenannt für Klarheit und $projectRoot eingeführt
    function load_theme_assets_from_manifest(string $actualThemeName, string $callingTemplateDir): ?array {
        $themeName = $actualThemeName; // Theme-Name direkt verwenden
        // Den Projekt-Root ableiten:
        // $callingTemplateDir ist z.B. /path/to/project/templates/themename/
        // dirname($callingTemplateDir) ist /path/to/project/templates/
        // dirname($callingTemplateDir, 2) ist /path/to/project/ (der gewünschte Root)
        $projectRoot = dirname($callingTemplateDir, 2);

        if (!$themeName) {
            // error_log("Theme Asset Loader: Konnte Theme-Namen nicht aus Template-Informationen extrahieren.");
            return null;
        }

        // Pfade mit $projectRoot anstelle von TL_ROOT erstellen
        $manifestBaseDir = $projectRoot . '/files/base/layout/_vendor/_dist-manifest/_' . $themeName;
        $jsManifestPath = $manifestBaseDir . '/app-js.manifest.json';
        $cssManifestPath = $manifestBaseDir . '/css.manifest.json'; // Einheitliches CSS-Manifest

        $themeAssets = ['js_app' => [], 'css' => [], 'js_vendor_individual' => [], 'fonts_preload' => []];

        // App JS-Assets aus app-js.manifest.json laden
        if (file_exists($jsManifestPath)) {
            $jsManifestContent = file_get_contents($jsManifestPath);
            $jsManifest = json_decode($jsManifestContent, true);
            if ($jsManifest) {
                foreach ($jsManifest as $originalName => $assetData) {
                    $srcPath = is_array($assetData) && isset($assetData['path']) ? $assetData['path'] : $assetData;
                    if (is_string($srcPath)) {
                        // Der Filter in Webpack stellt sicher, dass dies das Haupt-App-Bundle ist.
                        $themeAssets['js_app'][] = ['src' => $srcPath, 'name' => $originalName, 'defer' => true, 'type' => 'module'];
                    }
                }
            }
        }

        // CSS-Assets und Font-Preloads aus css.manifest.json laden
        if (file_exists($cssManifestPath)) {
            $cssManifestContent = file_get_contents($cssManifestPath);
            $cssManifest = json_decode($cssManifestContent, true);

            if (is_array($cssManifest)) {
                foreach ($cssManifest as $keyInManifest => $assetData) {
                    // Primärer Pfad: Verarbeitet die von Webpack erstellte Struktur (Objekt-Wert)
                    if (is_array($assetData) && isset($assetData['path']) && is_string($assetData['path'])) {
                        $publicPath = $assetData['path'];
                        $isFont = isset($assetData['isFont']) && $assetData['isFont'] === true;
                        $entryPoint = $assetData['entryPoint'] ?? null;
                        $assetOutputName = $assetData['name'] ?? basename($publicPath);

                        if ($isFont) {
                            $fontType = match (strtolower(pathinfo($publicPath, PATHINFO_EXTENSION))) {
                                'woff2' => 'font/woff2', 'woff'  => 'font/woff',
                                'ttf'   => 'font/ttf', 'otf'   => 'font/otf',
                                'svg'   => 'image/svg+xml', default => '',
                            };
                            if ($fontType) {
                                $found = false;
                                foreach ($themeAssets['fonts_preload'] as $existingFont) {
                                    if ($existingFont['href'] === $publicPath) { $found = true; break; }
                                }
                                if (!$found) {
                                    $themeAssets['fonts_preload'][] = ['href' => $publicPath, 'type' => $fontType, 'name' => $assetOutputName];
                                }
                            }
                        } elseif (pathinfo($publicPath, PATHINFO_EXTENSION) === 'css') {
                            $isDuplicate = false;
                            foreach ($themeAssets['css'] as $existingCss) {
                                if ($existingCss['path'] === $publicPath) { $isDuplicate = true; break; }
                            }
                            if (!$isDuplicate) {
                                $themeAssets['css'][] = [
                                    'path' => $publicPath,
                                    'entryPoint' => $entryPoint ?? pathinfo($keyInManifest, PATHINFO_FILENAME) // Fallback für EntryPoint
                                ];
                            }
                        }
                    } 
                    // Fallback-Pfad: Verarbeitet eine flache Key-Value-Struktur im Manifest
                    else if (is_string($assetData) && is_string($keyInManifest)) {
                        $publicPath = $assetData; // Wert ist der Pfad
                        $originalNameAsKey = $keyInManifest; // Schlüssel ist der Originalname (z.B. _vendors.scss)

                        $fileExtension = strtolower(pathinfo($publicPath, PATHINFO_EXTENSION));
                        $derivedEntryPoint = pathinfo($originalNameAsKey, PATHINFO_FILENAME); // z.B. _vendors

                        if (in_array($fileExtension, ['woff', 'woff2', 'ttf', 'otf', 'eot', 'svg'])) {
                            $fontType = match ($fileExtension) {
                                'woff2' => 'font/woff2', 'woff'  => 'font/woff',
                                'ttf'   => 'font/ttf', 'otf'   => 'font/otf',
                                'svg'   => 'image/svg+xml', default => '',
                            };
                            if ($fontType) {
                                $found = false;
                                foreach ($themeAssets['fonts_preload'] as $existingFont) {
                                    if ($existingFont['href'] === $publicPath) { $found = true; break; }
                                }
                                if (!$found) {
                                    $themeAssets['fonts_preload'][] = ['href' => $publicPath, 'type' => $fontType, 'name' => basename($publicPath)];
                                }
                            }
                        } elseif ($fileExtension === 'css') {
                            $isDuplicate = false;
                            foreach ($themeAssets['css'] as $existingCss) {
                                if ($existingCss['path'] === $publicPath) { $isDuplicate = true; break; }
                            }
                            if (!$isDuplicate) {
                                $themeAssets['css'][] = [
                                    'path' => $publicPath,
                                    'entryPoint' => $derivedEntryPoint
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Individuelle Vendor JS-Dateien laden (Pfade zeigen jetzt auf das 'dist/THEMENAME/vendor' Verzeichnis)
        $themeJsDir = $projectRoot . '/files/base/layout/js/_' . $themeName;
        $individualVendorJsConfigFile = $themeJsDir . '/theme.js_vendors.php';

        if (file_exists($individualVendorJsConfigFile)) {
            $vendorScriptConfigs = require $individualVendorJsConfigFile;
            if (is_array($vendorScriptConfigs)) {
                $vendorDestWebPathBase = '/files/base/layout/js/dist/' . $themeName . '/vendor/';
                foreach ($vendorScriptConfigs as $scriptConfig) {
                    if (is_array($scriptConfig) && isset($scriptConfig['src']) && isset($scriptConfig['attributes'])) {
                        $fileName = pathinfo($scriptConfig['src'], PATHINFO_BASENAME);
                        $finalVendorWebPath = $vendorDestWebPathBase . $fileName;
                        $themeAssets['js_vendor_individual'][] = [
                            'src' => $finalVendorWebPath,
                            'attributes' => $scriptConfig['attributes']
                        ];
                    }
                }
            }
        }

        return $themeAssets;
    }
} 