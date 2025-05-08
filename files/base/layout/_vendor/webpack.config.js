const fs = require('fs');
const path = require('path');
const glob = require('glob');
const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

// __dirname ist jetzt /Users/christian.voss/PhpstormProjects/Caeli-Relaunch/files/base/layout/_vendor

// Basispfad zu deinen JS-Dateien (eine Ebene höher, dann in 'js')
const jsWorkspaceBase = path.resolve(__dirname, '../js');
// Zielverzeichnis für die Bundles (eine Ebene höher, dann in 'js/dist')
const distDir = path.resolve(__dirname, '../js/dist');
// Projekt-Root-Verzeichnis (vier Ebenen höher von _vendor)
const projectRoot = path.resolve(__dirname, '../../../..');

// Basispfad zum node_modules Ordner (innerhalb von _vendor)
const nodeModulesPath = path.resolve(__dirname, 'node_modules');

// --- NEU: CSS-spezifische Pfade ---
const cssWorkspaceBase = path.resolve(__dirname, '../css');

// NEU: Basispfad für Manifeste
const manifestBasePath = path.resolve(__dirname, '_dist-manifest');

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
const jsWebpackConfigs = themeFolders.flatMap(theme => {
    const themeNameRaw = theme.name; // z.B. _caeliRelaunch
    const themeNameClean = themeNameRaw.startsWith('_') ? themeNameRaw.substring(1) : themeNameRaw; // z.B. caeliRelaunch

    const themePath = theme.path;
    const themeJsDistDir = path.resolve(jsWorkspaceBase, 'dist');
    const themeJsPublicPath = '/files/base/layout/js/dist/';
    const themeManifestDir = path.join(manifestBasePath, '_' + themeNameClean);

    if (!fs.existsSync(themeManifestDir)) {
        fs.mkdirSync(themeManifestDir, { recursive: true });
    }

    // Alle .js-Dateien im aktuellen Theme-Ordner (und seinen Unterordnern, falls vorhanden)
    const themeSpecificAppJsFiles = glob.sync(path.join(themePath, '**/*.js').replace(/\\/g, '/'));

    // Alle Eingabedateien für dieses App-Bundle
    const appEntryFiles = [
        ...baseJsFiles,
        ...themeSpecificAppJsFiles
    ];

    console.log(`Für Theme "${themeNameClean}" (App JS) werden folgende Dateien gebündelt:`);
    appEntryFiles.forEach(file => console.log(`  - ${path.relative(projectRoot, file)}`));

    const appBundleConfig = {
        name: `${themeNameClean}-app`,
        mode: 'production',
        entry: appEntryFiles,
        output: {
            filename: `${themeNameClean}.bundle.min.js`,
            path: themeJsDistDir,
            publicPath: themeJsPublicPath,
            clean: false,
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
        plugins: [
            new WebpackManifestPlugin({
                fileName: path.join(themeManifestDir, 'app-js.manifest.json'),
                publicPath: themeJsPublicPath,
                filter: (file) => file.isInitial && file.name.endsWith('.js'),
                map: (file) => ({
                    name: file.name,
                    path: file.path,
                    isInitial: file.isInitial,
                    isChunk: file.isChunk,
                    entryPoint: file.chunk?.name
                })
            })
        ]
    };

    // --- Vendor Bundle Config (NEU) ---
    let vendorEntryFiles = [];
    const themeVendorConfigFileJs = path.join(themePath, 'theme.vendors.js');
    let vendorBundleConfig = null;

    if (fs.existsSync(themeVendorConfigFileJs)) {
        try {
            const themeSpecificVendorPaths = require(themeVendorConfigFileJs);
            if (Array.isArray(themeSpecificVendorPaths)) {
                vendorEntryFiles = themeSpecificVendorPaths.map(pkgRelativePath => {
                    const fullPath = path.join(nodeModulesPath, pkgRelativePath);
                    if (fs.existsSync(fullPath)) {
                        return fullPath;
                    }
                    console.warn(`Warnung: Vendor-Pfad "${pkgRelativePath}" in ${themeVendorConfigFileJs} nicht gefunden.`);
                    return null;
                }).filter(Boolean);
            } else {
                console.warn(`Warnung: ${themeVendorConfigFileJs} hat kein Array exportiert.`);
            }
        } catch (e) {
            console.error(`Fehler beim Laden von ${themeVendorConfigFileJs}:`, e);
        }
    }

    if (vendorEntryFiles.length > 0) {
        console.log(`Für Theme "${themeNameClean}" (Vendors JS) werden folgende Dateien gebündelt:`);
        vendorEntryFiles.forEach(file => console.log(`  - ${path.relative(projectRoot, file)}`));

        vendorBundleConfig = {
            name: `${themeNameClean}-vendors`,
            mode: 'production',
            entry: vendorEntryFiles,
            output: {
                filename: `${themeNameClean}_vendors.bundle.min.js`,
                path: themeJsDistDir,
                publicPath: themeJsPublicPath,
                clean: false,
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
            plugins: [
                new WebpackManifestPlugin({
                    fileName: path.join(themeManifestDir, 'vendor-js.manifest.json'),
                    publicPath: themeJsPublicPath,
                    filter: (file) => file.isInitial && file.name.endsWith('.js'),
                    map: (file) => ({
                        name: file.name,
                        path: file.path,
                        isInitial: file.isInitial,
                        isChunk: file.isChunk,
                        entryPoint: file.chunk?.name
                    })
                })
            ]
        };
    }

    // Gebe ein Array von Konfigurationen für dieses Theme zurück
    return vendorBundleConfig ? [appBundleConfig, vendorBundleConfig] : [appBundleConfig];
});

// --- SCSS-Konfigurationen ---
const mainScssFiles = glob.sync(path.join(cssWorkspaceBase, '*.scss').replace(/\\/g, '/'))
    .filter(file => !path.basename(file).startsWith('_'));

console.log('Gefundene Haupt-SCSS-Dateien:', mainScssFiles);

const cssWebpackConfigs = mainScssFiles.map(scssFile => {
    const themeNameClean = path.basename(scssFile, '.scss'); // z.B. caeliRelaunch
    const entryName = themeNameClean;

    const themeCssDir = path.join(cssWorkspaceBase, '_' + themeNameClean);
    const themeCssDistDir = path.join(themeCssDir, 'dist');
    const themePublicPath = `/files/base/layout/css/_${themeNameClean}/dist/`;
    const themeManifestDir = path.join(manifestBasePath, '_' + themeNameClean);

    if (!fs.existsSync(themeCssDir)) {
        console.warn(`Theme-Verzeichnis ${path.relative(projectRoot, themeCssDir)} nicht gefunden. Erstelle es nicht automatisch.`);
    }
    if (!fs.existsSync(themeCssDistDir)) {
        fs.mkdirSync(themeCssDistDir, { recursive: true });
        console.log(`   Erstelle themenspezifisches dist-Verzeichnis: ${path.relative(projectRoot, themeCssDistDir)}`);
    }

    if (!fs.existsSync(themeManifestDir)) {
        fs.mkdirSync(themeManifestDir, { recursive: true });
    }

    console.log(`CSS-Konfig für Theme "${themeNameClean}":`);
    console.log(`   SCSS-Datei: ${path.relative(projectRoot, scssFile)}`);
    console.log(`   Ausgabe (output.path): ${path.relative(projectRoot, themeCssDistDir)}`);
    console.log(`   Public Path (output.publicPath): ${themePublicPath}`);

    let themeAliases = {};
    const themeAliasConfigFile = path.join(themeCssDir, 'theme.fontaliases.js');
    if (fs.existsSync(themeAliasConfigFile)) {
        try {
            const loadedAliasesRelative = require(themeAliasConfigFile);
            for (const key in loadedAliasesRelative) {
                themeAliases[key] = path.resolve(nodeModulesPath, loadedAliasesRelative[key]);
            }
            console.log(`   Lade Font-Aliase aus ${path.relative(projectRoot, themeAliasConfigFile)}`);
        } catch (e) {
            console.error(`   Fehler beim Laden von ${themeAliasConfigFile}:`, e);
        }
    } else {
        console.log(`   Keine theme.fontaliases.js für Theme "${themeNameClean}" gefunden in ${path.relative(projectRoot, themeCssDir)}`);
    }

    return {
        name: `${themeNameClean}-css`,
        mode: 'production',
        entry: {
            [entryName]: scssFile
        },
        output: {
            path: themeCssDistDir,
            publicPath: themePublicPath,
            assetModuleFilename: 'fonts/[hash][ext][query]',
            clean: false,
        },
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: [
                        {
                            loader: MiniCssExtractPlugin.loader,
                            options: {
                                // publicPath sollte jetzt durch output.publicPath abgedeckt sein
                            },
                        },
                        'css-loader',
                        {
                            loader: 'sass-loader',
                            options: {
                                sassOptions: {
                                    outputStyle: 'compressed',
                                },
                            },
                        },
                    ],
                },
                {
                    test: /\.(woff|woff2|eot|ttf|otf|svg)$/i,
                    type: 'asset/resource',
                }
            ],
        },
        plugins: [
            new MiniCssExtractPlugin({
                filename: '[name].bundle.min.css',
            }),
            new WebpackManifestPlugin({
                fileName: path.join(themeManifestDir, 'css.manifest.json'),
                publicPath: themePublicPath,
                filter: (file) => !file.name.endsWith('.js'),
                map: (file) => ({
                    name: file.name,
                    path: file.path,
                    isInitial: file.isInitial,
                    isChunk: file.isChunk,
                    entryPoint: file.chunk?.name
                })
            })
        ],
        optimization: {
            minimize: true,
            minimizer: [
                new CssMinimizerPlugin(),
            ],
        },
        resolve: {
            alias: themeAliases,
        },
        performance: {
            hints: false
        }
    };
});

// Kombinieren von JS und CSS Konfigurationen
let allConfigs = [];
if (jsWebpackConfigs && jsWebpackConfigs.length > 0) {
    allConfigs = allConfigs.concat(jsWebpackConfigs.flat());
}
if (cssWebpackConfigs && cssWebpackConfigs.length > 0) {
    allConfigs = allConfigs.concat(cssWebpackConfigs.filter(Boolean));
}

if (allConfigs.length === 0) {
    console.warn("Keine Konfigurationen (JS oder CSS) zum Erstellen gefunden.");
    module.exports = {};
} else {
    console.log(`Exportiere insgesamt ${allConfigs.length} Webpack-Konfigurationen.`);
    allConfigs.forEach(conf => console.log(` - Konfigurationsname: ${conf.name}, Ausgabe nach: ${conf.output ? path.relative(projectRoot, conf.output.path) : 'N/A'}`));
    module.exports = allConfigs;
}
