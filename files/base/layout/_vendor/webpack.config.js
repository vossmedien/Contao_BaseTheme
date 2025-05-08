const fs = require('fs');
const path = require('path');
const glob = require('glob');
const TerserPlugin = require('terser-webpack-plugin');

// __dirname ist jetzt /Users/christian.voss/PhpstormProjects/Caeli-Relaunch/files/base/layout/_vendor

// Basispfad zu deinen JS-Dateien (eine Ebene höher, dann in 'js')
const jsWorkspaceBase = path.resolve(__dirname, '../js');
// Zielverzeichnis für die Bundles (eine Ebene höher, dann in 'js/dist')
const distDir = path.resolve(__dirname, '../js/dist');
// Projekt-Root-Verzeichnis (vier Ebenen höher von _vendor)
const projectRoot = path.resolve(__dirname, '../../../..');

// Basispfad zum node_modules Ordner (innerhalb von _vendor)
const nodeModulesPath = path.resolve(__dirname, 'node_modules');

// 1. Basis-JavaScript-Dateien (direkt unter /js, nicht in Unterordnern außer _elements und _vendor)
const baseJsFiles = glob.sync(path.join(jsWorkspaceBase, '*.js').replace(/\\/g, '/'))
    .filter(filePath => {
        // Hier könnten wir noch spezifischer filtern, falls nötig.
        return true;
    }); 

// 2. Theme-Ordner identifizieren (alle Ordner unter /js außer _elements, _vendor und dist)
const themeFolders = fs.readdirSync(jsWorkspaceBase)
    .map(item => ({ name: item, path: path.join(jsWorkspaceBase, item) }))
    .filter(item => fs.statSync(item.path).isDirectory())
    .filter(item => !['_elements', '_vendor', 'dist'].includes(item.name) && !item.name.startsWith('.')); // Ignoriere .DS_Store etc.

// 3. Webpack-Konfigurationen für jedes Theme-Bundle dynamisch erstellen
const webpackConfigs = themeFolders.flatMap(theme => {
    const themeName = theme.name;
    const themePath = theme.path;

    // Alle .js-Dateien im aktuellen Theme-Ordner (und seinen Unterordnern, falls vorhanden)
    const themeSpecificAppJsFiles = glob.sync(path.join(themePath, '**/*.js').replace(/\\/g, '/'));

    // Alle Eingabedateien für dieses App-Bundle
    const appEntryFiles = [
        ...baseJsFiles,
        ...themeSpecificAppJsFiles
    ];

    console.log(`Für Theme "${themeName}" (App) werden folgende Dateien gebündelt:`);
    appEntryFiles.forEach(file => console.log(`  - ${path.relative(projectRoot, file)}`));

    const appBundleConfig = {
        name: `${themeName}-app`, // Eindeutiger Name für die Konfiguration
        mode: 'production',
        entry: appEntryFiles,
        output: {
            filename: `${themeName}.bundle.min.js`,
            path: distDir,
        },
        module: {
            rules: [
                {
                    test: /\.js$/,
                    exclude: /node_modules/,
                    use: {
                        loader: 'babel-loader',
                        // Babel-Optionen werden aus babel.config.js gelesen
                    },
                },
            ],
        },
        optimization: {
            minimize: true,
            minimizer: [
                new TerserPlugin({
                    extractComments: false,
                    terserOptions: {
                        format: {
                            comments: false,
                        },
                    },
                }),
            ],
        },
        performance: {
            hints: false
        },
        resolve: {
            extensions: ['.js'],
        },
    };

    // --- Vendor Bundle Config (NEU) ---
    let vendorEntryFiles = [];
    const themeVendorConfigFile = path.join(themePath, 'theme.vendors.js');
    let vendorBundleConfig = null;

    if (fs.existsSync(themeVendorConfigFile)) {
        try {
            const themeSpecificVendorPaths = require(themeVendorConfigFile);
            if (Array.isArray(themeSpecificVendorPaths)) {
                vendorEntryFiles = themeSpecificVendorPaths.map(pkgRelativePath => {
                    const fullPath = path.join(nodeModulesPath, pkgRelativePath);
                    if (fs.existsSync(fullPath)) {
                        return fullPath;
                    }
                    console.warn(`Warnung: Vendor-Pfad "${pkgRelativePath}" (aufgelöst zu ${fullPath}) in ${themeVendorConfigFile} nicht gefunden.`);
                    return null;
                }).filter(Boolean); // Entfernt null-Werte, falls ein Pfad nicht gefunden wurde
            } else {
                console.warn(`Warnung: ${themeVendorConfigFile} hat kein Array exportiert.`);
            }
        } catch (e) {
            console.error(`Fehler beim Laden von ${themeVendorConfigFile}:`, e);
        }
    }

    if (vendorEntryFiles.length > 0) {
        console.log(`Für Theme "${themeName}" (Vendors) werden folgende Dateien gebündelt:`);
        vendorEntryFiles.forEach(file => console.log(`  - ${path.relative(projectRoot, file)}`));

        vendorBundleConfig = {
            name: `${themeName}-vendors`, // Eindeutiger Name
            mode: 'production',
            entry: vendorEntryFiles,
            output: {
                filename: `${themeName}_vendors.bundle.min.js`, // Z.B. _caeli_relaunch_vendors.bundle.min.js
                path: distDir,
            },
            module: {
                rules: [
                    {
                        test: require.resolve('bootstrap/dist/js/bootstrap.bundle.min.js'),
                        use: [{ loader: 'expose-loader', options: { exposes: ['bootstrap'] } }]
                    },
                    {
                        test: require.resolve('venobox/dist/venobox.min.js'),
                        use: [{ loader: 'expose-loader', options: { exposes: ['VenoBox'] } }]
                    }
                    // Hier könnten weitere expose-loader Regeln für andere globale Bibliotheken folgen
                ]
            },
            optimization: {
                minimize: true,
                minimizer: [
                    new TerserPlugin({
                        extractComments: false,
                        terserOptions: {
                            format: {
                                comments: false,
                            },
                        },
                    }),
                ],
            },
            performance: {
                hints: false
            },
            resolve: {
                extensions: ['.js'],
            },
        };
    }

    // Gebe ein Array von Konfigurationen für dieses Theme zurück
    return vendorBundleConfig ? [appBundleConfig, vendorBundleConfig] : [appBundleConfig];
});

if (webpackConfigs.length === 0) {
    console.warn("Keine Theme-Ordner gefunden, für die Bundles erstellt werden könnten. Bitte überprüfe die Struktur unter " + jsWorkspaceBase);
    module.exports = {}; // Leere Konfiguration, wenn keine Themes gefunden wurden
} else {
    // Sicherstellen, dass webpackConfigs eine flache Liste ist (flatMap sollte das bereits tun)
    module.exports = webpackConfigs.flat();
}
