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
        // Der Pfad zum CSS-Manifest muss ggf. auch überprüft werden, ob er noch stimmt oder aus der Webpack-Config kommt.
        // Annahme: Webpack legt CSS-Manifeste pro Theme-CSS-Bundle in $manifestBaseDir / $themeNameRaw (z.B. _kgdental) / bundle-css.manifest.json ab
        // Für die Vereinfachung hier, nehmen wir an, es gibt ein allgemeines CSS-Manifest pro Theme.
        // Basierend auf der fe_page.html5 scheint es, als ob $themeAssets['css'] direkt die Pfade enthält.
        // Die CSS-Manifest-Logik in dieser Funktion wird für individuelle CSS-Dateien hier nicht mehr benötigt,
        // da fe_page.html5 dies direkt aus $themeAssets['css'] (gefüllt durch Webpack-Manifeste) handhabt.
        // Wir konzentrieren uns hier auf js_app und js_vendor_individual.

        $themeAssets = ['js_app' => [], 'css' => [], 'js_vendor_individual' => [], 'fonts_preload' => []];

        // Logik zum Laden von CSS und Fonts aus Manifesten (aus der ursprünglichen Version, falls benötigt, aber fe_page.html5 scheint dies zu überschreiben)
        // Für diese Anpassung lasse ich die CSS/Font-Manifest-Logik hier erstmal wie sie war,
        // aber $projectRoot muss hier auch verwendet werden, wenn diese Teile aktiv sind.
        // Die angehängte theme_asset_loader.php-Version vor diesem Edit hatte eine komplexere CSS/Font-Verarbeitung.
        // Ich fokussiere mich auf die TL_ROOT Korrektur für JS.

        // Die alte $processSingleManifestFile Closure ist hier nicht mehr, da die Logik für CSS/Fonts sich geändert hat.
        // Die `fe_page.html5` holt sich `$themeAssets['css']` und `$themeAssets['fonts_preload']`
        // vermutlich aus Manifesten, die von einer aktuelleren Webpack-Konfiguration erzeugt werden.
        // Diese Funktion hier muss nur noch die JS-Teile korrekt zusammenstellen.

        // App JS-Assets aus Manifest laden (wenn app-js.manifest.json existiert)
        if (file_exists($jsManifestPath)) {
            $jsManifestContent = file_get_contents($jsManifestPath);
            $jsManifest = json_decode($jsManifestContent, true);
            if ($jsManifest) {
                foreach ($jsManifest as $originalName => $hashedNameOrPath) {
                    // WebpackManifestPlugin gibt oft ein Objekt mit 'path' zurück oder direkt den Pfad
                    $srcPath = is_array($hashedNameOrPath) && isset($hashedNameOrPath['path']) ? $hashedNameOrPath['path'] : $hashedNameOrPath;
                    if (is_string($srcPath)) {
                         // Stelle sicher, dass es sich um ein App-Bundle handelt (und nicht versehentlich ein Vendor-Bundle, falls das Manifest das enthalten würde)
                        if (str_contains($srcPath, $themeName . '.bundle.min.js') && !str_contains($srcPath, '_vendors.bundle.min.js')) {
                             $themeAssets['js_app'][] = ['src' => $srcPath, 'name' => $originalName, 'defer' => true, 'type' => 'module']; // Annahme für App-JS
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
                // NEU: Korrekter Web-Pfad zur Basis des Vendor-Verzeichnisses für dieses Theme
                $vendorDestWebPathBase = '/files/base/layout/js/dist/' . $themeName . '/vendor/';
                foreach ($vendorScriptConfigs as $scriptConfig) {
                    if (is_array($scriptConfig) && isset($scriptConfig['src']) && isset($scriptConfig['attributes'])) {
                        // $scriptConfig['src'] ist der Pfad relativ zu node_modules, z.B. 'js-cookie/dist/js.cookie.min.js'
                        // Wir brauchen nur den Dateinamen für den Zielpfad.
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

        // CSS und Font Preload (basierend auf der vorherigen Struktur der Funktion, Pfade prüfen!)
        // Die CSS-Pfade in $themeAssets['css'] werden aus den CSS-Manifesten kommen.
        // Diese Manifeste werden mit einem publicPath wie /files/base/layout/css/_THEMENAME/dist/ erstellt.
        // Das sollte also weiterhin stimmen, da CSS eine eigene dist-Struktur hat.
        $cssManifestDir = $projectRoot . '/files/base/layout/_vendor/_dist-manifest/_' . $themeName;
        $cssBundleOrder = ['_vendors', '_base', '_root-variables', '_theme', '_fonts'];
        foreach ($cssBundleOrder as $bundleName) {
            // Pfad zum spezifischen CSS-Manifest für das aktuelle Theme und Bundle-Typ
            // In Webpack wurde themeManifestDirForCss als path.join(manifestBasePath, themeNameRaw) definiert,
            // was zu _dist-manifest/_themename/ führen würde.
            $cssManifestFilePath = $projectRoot . '/files/base/layout/_vendor/_dist-manifest/_' . $themeName . '/' . $bundleName . '-css.manifest.json';
            if (file_exists($cssManifestFilePath)) {
                $manifestJson = file_get_contents($cssManifestFilePath);
                if ($manifestJson !== false) {
                    $manifestData = json_decode($manifestJson, true);
                    if (is_array($manifestData)) {
                        foreach ($manifestData as $sourceName => $publicPathOrObject) {
                            $publicPath = is_array($publicPathOrObject) && isset($publicPathOrObject['path']) ? $publicPathOrObject['path'] : $publicPathOrObject;
                            if (!is_string($publicPath) || empty($publicPath)) continue;

                            $currentFilenameForLogic = $sourceName; // In Webpack ist der key oft der Dateiname des Chunks
                            // Oder wir nehmen basename($publicPath), wenn sourceName nicht passt
                            if (pathinfo($publicPath, PATHINFO_EXTENSION) === 'css') { // Sicherstellen, dass es eine CSS-Datei ist
                                // publicPath sollte bereits der korrekte Webpfad sein, z.B. /files/base/layout/css/_themename/dist/_fonts.bundle.min.css
                                // substr($publicPath,1) ist nicht nötig, wenn der Pfad bereits korrekt ist.
                                if (!in_array($publicPath, $themeAssets['css'])) {
                                    $themeAssets['css'][] = $publicPath;
                                }
                            } else if (str_starts_with($publicPath, '/files/base/layout/css/') && str_contains($publicPath, '/fonts/') && preg_match('/\.(woff2|woff|ttf|otf|svg)$/i', $publicPath)) {
                                // Wenn Fonts direkt im CSS-Manifest mit ihrem vollen Pfad auftauchen
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
                                        $themeAssets['fonts_preload'][] = ['href' => $publicPath, 'type' => $fontType];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // Ende der beispielhaften CSS/Font-Manifest-Verarbeitung basierend auf der älteren Version

        return $themeAssets;
    }
} 