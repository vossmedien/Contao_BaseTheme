const fs = require('fs');
const path = require('path');
const glob = require('glob');
const TerserPlugin = require('terser-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const CopyWebpackPlugin = require('copy-webpack-plugin');

// __dirname ist jetzt /Users/christian.voss/PhpstormProjects/Caeli-Relaunch/files/base/layout/_vendor

// --- Gemeinsame Konfigurationsteile ---
const commonBabelLoaderRule = {
    test: /\.js$/,
    exclude: /node_modules/,
    use: {
        loader: 'babel-loader',
        // Babel-Optionen werden aus babel.config.js gelesen
    },
};

const commonTerserPlugin = new TerserPlugin({
    extractComments: false,
    terserOptions: {
        format: {
            comments: false,
        },
    },
});

const commonCssMinimizerPlugin = new CssMinimizerPlugin();

const commonPerformanceSettings = {
    hints: false,
};

const commonIgnoreWarningsSetting = [/Warning/];

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

// --- RSCE SCSS-Dateien Pfad ---
const rsceCssPath = path.resolve(cssWorkspaceBase, 'elements/rsce'); // NEU

// --- Hilfsfunktion für SCSS-Loader ---
function getScssLoaders(useResolveUrlLoader = false, sourceMap = false) {
    const loaders = [
        MiniCssExtractPlugin.loader,
        { loader: 'css-loader', options: { sourceMap: sourceMap } },
    ];
    if (useResolveUrlLoader) {
        loaders.push({ loader: 'resolve-url-loader', options: { sourceMap: sourceMap, removeCR: true } });
    }
    loaders.push({
        loader: 'sass-loader',
        options: {
            sourceMap: sourceMap, // sourceMap hier ist wichtig für resolve-url-loader, falls aktiv
            sassOptions: {
                outputStyle: 'compressed',
                quietDeps: true // NEU: Unterdrückt Warnungen von Abhängigkeiten
            }
        }
    });
    return loaders;
}

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
const jsAppWebpackConfigs = themeFolders.flatMap(theme => {
    const themeNameRaw = theme.name; // z.B. _caeliRelaunch
    const themeNameClean = themeNameRaw.startsWith('_') ? themeNameRaw.substring(1) : themeNameRaw; // z.B. caeliRelaunch

    const themePath = theme.path;
    const themeSpecificDistPath = path.resolve(jsWorkspaceBase, 'dist', themeNameClean);
    const themeSpecificPublicPath = `/files/base/layout/js/dist/${themeNameClean}/`;
    const themeManifestDir = path.join(manifestBasePath, '_' + themeNameClean);

    if (!fs.existsSync(themeManifestDir)) {
        fs.mkdirSync(themeManifestDir, { recursive: true });
    }
    if (!fs.existsSync(themeSpecificDistPath)) {
        fs.mkdirSync(themeSpecificDistPath, { recursive: true });
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

    // NEU: Vendor-Skripte für CopyWebpackPlugin aus theme.js_vendors.php lesen
    const vendorCopyPatterns = [];
    const themeVendorConfigPath = path.join(themePath, 'theme.js_vendors.php');
    if (fs.existsSync(themeVendorConfigPath)) {
        try {
            const phpContent = fs.readFileSync(themeVendorConfigPath, 'utf-8');
            const srcRegex = /['"]src['"]\s*=>\s*['"]([^'"]+\.js)['"]/g;
            let match;
            while ((match = srcRegex.exec(phpContent)) !== null) {
                const vendorSrc = match[1];
                vendorCopyPatterns.push({
                    from: path.resolve(nodeModulesPath, vendorSrc),
                    to: path.join(themeSpecificDistPath, 'vendor', path.basename(vendorSrc)),
                });
            }
            if (vendorCopyPatterns.length > 0) {
                console.log(`Für Theme "${themeNameClean}" werden ${vendorCopyPatterns.length} Vendor-Dateien nach ${path.relative(projectRoot, path.join(themeSpecificDistPath, 'vendor'))} kopiert.`);
            }
        } catch (e) {
            console.error(`Fehler beim Lesen oder Verarbeiten von ${themeVendorConfigPath} für Theme ${themeNameClean}:`, e);
        }
    }

    const appBundleConfig = {
        name: `app-${themeNameClean}-js`,
        mode: 'production',
        entry: appEntryFiles,
        output: {
            filename: `${themeNameClean}.[contenthash].bundle.min.js`,
            path: themeSpecificDistPath,
            publicPath: themeSpecificPublicPath,
            clean: {
                keep: /vendor\//,
            },
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
                commonTerserPlugin,
            ],
        },
        performance: commonPerformanceSettings,
        resolve: {
            extensions: ['.js'],
        },
        plugins: [
            new WebpackManifestPlugin({
                fileName: path.join(themeManifestDir, 'app-js.manifest.json'),
                publicPath: themeSpecificPublicPath,
                filter: (file) => file.isInitial && file.name.endsWith('.js'),
                map: (file) => ({
                    name: file.name,
                    path: file.path,
                    isInitial: file.isInitial,
                    isChunk: file.isChunk,
                    entryPoint: file.chunk?.name
                })
            }),
            new CopyWebpackPlugin({ patterns: vendorCopyPatterns })
        ],
    };

    // Gebe nur die appBundleConfig für dieses Theme zurück
    return [appBundleConfig]; // Muss immer noch ein Array sein, da flatMap es erwartet
});

// --- SCSS-Konfigurationen ---
// Alte Logik für mainScssFiles wird entfernt.

const cssThemeFolders = fs.readdirSync(cssWorkspaceBase)
    .map(item => ({ name: item, path: path.join(cssWorkspaceBase, item) }))
    .filter(item => {
        const stat = fs.statSync(item.path);
        // Nur Ordner, die mit '_' beginnen und nicht '_dist-manifest' sind.
        // Und stelle sicher, dass es wirklich ein Ordner ist.
        return stat.isDirectory() && item.name.startsWith('_') && item.name !== '_dist-manifest';
    });

console.log('Gefundene CSS-Theme-Ordner:', cssThemeFolders.map(f => f.name));

// const cleanedCssThemeDistDirs = new Set(); // Entfernt für den neuen Ansatz

const cssThemeWebpackConfigs = cssThemeFolders.map(themeFolder => { // Geändert von flatMap zu map
    const themeNameRaw = themeFolder.name; // z.B. _caeliRelaunch
    const themeNameClean = themeNameRaw.substring(1); // z.B. caeliRelaunch
    const themeCssDir = themeFolder.path; // z.B. /path/to/files/base/layout/css/_caeliRelaunch
    const themeCssDistDir = path.join(themeCssDir, 'dist');
    const themePublicPath = `/files/base/layout/css/${themeNameRaw}/dist/`;
    const themeManifestDir = path.join(manifestBasePath, themeNameRaw); // Manifeste im Ordner _caeliRelaunch etc.

    // Sicherstellen, dass die Verzeichnisse existieren
    if (!fs.existsSync(themeCssDistDir)) {
        fs.mkdirSync(themeCssDistDir, { recursive: true });
    }
    if (!fs.existsSync(themeManifestDir)) {
        fs.mkdirSync(themeManifestDir, { recursive: true });
    }

    const scssFilesToBundle = [
        '_vendors.scss',
        '_base.scss',
        '_theme.scss',
        '_root-variables.scss',
        '_fonts.scss',
        '_utilities.scss'
    ];

    const entryPoints = {};
    scssFilesToBundle.forEach(scssFileName => {
        const scssFilePath = path.join(themeCssDir, scssFileName);
        if (fs.existsSync(scssFilePath)) {
            const entryName = scssFileName.replace('.scss', '');
            entryPoints[entryName] = scssFilePath;
        } else {
            console.warn(`   SCSS-Datei ${scssFileName} nicht in ${themeNameRaw} gefunden unter ${path.relative(projectRoot, scssFilePath)}. Überspringe für Multi-Entry.`);
        }
    });

    if (Object.keys(entryPoints).length === 0) {
        console.warn(`   Keine gültigen SCSS-Einstiegspunkte für Theme ${themeNameRaw} gefunden. Überspringe CSS-Konfiguration für dieses Theme.`);
        return null; 
    }

    console.log(`CSS-Konfig für Theme "${themeNameRaw}" mit folgenden Entry-Points:`, entryPoints);

    return { 
        name: `theme-${themeNameClean}-css-bundle`,
        mode: 'production',
        entry: entryPoints,
        output: {
            path: themeCssDistDir,
            publicPath: themePublicPath,
            filename: '[name].remove.js',// Platzhalter für JS, wird von RemoveEmptyScriptsPlugin entfernt
            assetModuleFilename: 'fonts/[name].[hash][ext][query]', // Fonts wieder in Unterordner
            clean: true, // output.clean für diese eine Konfiguration pro Theme
        },
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: getScssLoaders(true, true),
                },
                {
                    test: /\.(woff|woff2|eot|ttf|otf|svg)$/i,
                    type: 'asset/resource',
                }
            ],
        },
        plugins: [
            new RemoveEmptyScriptsPlugin(),
            new MiniCssExtractPlugin({
                filename: '[name].[contenthash].bundle.min.css',
            }),
            new WebpackManifestPlugin({
                fileName: path.join(themeManifestDir, `css.manifest.json`), // Ein Manifest pro Theme für alle CSS
                publicPath: themePublicPath,
                // Filter erweitert, um auch Font-Dateien einzuschließen
                filter: (file) => file.name.endsWith('.css') || /\.(woff2?|eot|ttf|otf|svg)$/i.test(file.name),
                map: (file) => {
                    const entryPoint = file.chunk?.name; // Nur für CSS-Chunks relevant
                    const isFontFile = /\.(woff2?|eot|ttf|otf|svg)$/i.test(file.name);
                    return {
                        name: file.name, // z.B. _vendors.hash.bundle.min.css oder fontname.hash.woff2
                        path: file.path, // Der volle Pfad inkl. publicPath
                        isInitial: file.isInitial,
                        isChunk: file.isChunk,
                        // Fonts haben keinen CSS-Entry-Point, CSS-Dateien schon
                        entryPoint: isFontFile ? null : entryPoint,
                        isFont: isFontFile // Zusätzliches Flag zur einfachen Identifizierung
                    };
                }
            }),
        ],
        optimization: {
            minimize: true,
            minimizer: [
                commonCssMinimizerPlugin,
            ],
        },
        resolve: {
            alias: {}, // themeAliases war vorher pro Datei, hier vereinfacht
        },
        performance: commonPerformanceSettings,
        ignoreWarnings: commonIgnoreWarningsSetting,
    };
}).filter(Boolean); // Entferne null-Werte, falls ein Theme keine gültigen SCSS-Dateien hatte

// --- NEU: RSCE SCSS-Konfigurationen ---
const rsceScssFiles = glob.sync(path.join(rsceCssPath, '*.scss').replace(/\\/g, '/'));
console.log('Gefundene RSCE SCSS-Dateien:', rsceScssFiles.map(f => path.relative(projectRoot, f)));

const rsceWebpackConfigs = rsceScssFiles.map(scssFile => {
    const fileNameWithoutExt = path.basename(scssFile, '.scss'); // z.B. ce_rsce_videogrid
    const outputDir = path.dirname(scssFile); // Das Verzeichnis der Quelldatei, z.B. .../rsce/
    const outputCssFilePath = path.join(outputDir, `${fileNameWithoutExt}.min.css`);

    return {
        name: `rsce-${fileNameWithoutExt}-css`, // Eindeutiger Name, z.B. rsce-ce_rsce_videogrid-css
        mode: 'production',
        entry: {
            // Entry-Key ist der Dateiname ohne .scss, damit [name].min.css den korrekten Namen bekommt
            [fileNameWithoutExt]: scssFile,
        },
        output: {
            path: outputDir, // Ausgabe ins selbe Verzeichnis wie die Quelldatei
            // filename ist für JS, wird aber von RemoveEmptyScriptsPlugin entfernt.
            // Css wird durch MiniCssExtractPlugin.filename gesteuert.
            filename: '[name].js', // Platzhalter, wird entfernt
            publicPath: '', // Nicht relevant, da lokal ausgegeben und nicht über Webpfad geladen (für Manifest)
            clean: false, // Nicht das Verzeichnis für jede Datei leeren
        },
        module: {
            rules: [
                {
                    test: /\.scss$/,
                    use: getScssLoaders(false, false),
                },
            ],
        },
        plugins: [
            new RemoveEmptyScriptsPlugin(),
            new MiniCssExtractPlugin({
                filename: '[name].min.css', // Erzeugt z.B. ce_rsce_videogrid.min.css
            }),
        ],
        optimization: {
            minimize: true,
            minimizer: [commonCssMinimizerPlugin],
        },
        performance: commonPerformanceSettings,
        ignoreWarnings: commonIgnoreWarningsSetting,
    };
});

// --- NEU: Element JS-Konfigurationen ---
const elementsJsPath = path.resolve(jsWorkspaceBase, '_elements'); // Korrekt hier oben definiert
const elementJsFiles = glob.sync(path.join(elementsJsPath, '*.js').replace(/\\/g, '/'))
    .filter(file => !file.endsWith('.min.js')); // Bereits minifizierte Dateien ignorieren
console.log('Gefundene Element JS-Dateien:', elementJsFiles.map(f => path.relative(projectRoot, f)));

const jsElementWebpackConfigs = elementJsFiles.map(jsFile => {
    const fileNameWithoutExt = path.basename(jsFile, '.js');
    const outputDir = path.dirname(jsFile);
    return {
        name: `element-${fileNameWithoutExt}-js`, // Name ist schon passend
        mode: 'production',
        entry: {
            [fileNameWithoutExt]: jsFile,
        },
        output: {
            path: outputDir,
            filename: '[name].min.js', // Sollte examplename.min.js erzeugen
            publicPath: '',
            clean: false,
        },
        module: {
            rules: [
                { test: /\.js$/, exclude: /node_modules/, use: { loader: 'babel-loader' } }
            ],
        },
        optimization: {
            minimize: true,
            minimizer: [new TerserPlugin({ extractComments: false, terserOptions: { format: { comments: false } } })],
        },
        performance: { hints: false },
        watchOptions: {
            poll: 1000,
            aggregateTimeout: 600
        }
    };
});

// Kombinieren von JS und CSS Konfigurationen
let allConfigs = [];
if (jsAppWebpackConfigs && jsAppWebpackConfigs.length > 0) {
    allConfigs = allConfigs.concat(jsAppWebpackConfigs.flat());
}
if (cssThemeWebpackConfigs && cssThemeWebpackConfigs.length > 0) {
    allConfigs = allConfigs.concat(cssThemeWebpackConfigs.filter(Boolean));
}
if (rsceWebpackConfigs && rsceWebpackConfigs.length > 0) {
    allConfigs = allConfigs.concat(rsceWebpackConfigs.filter(Boolean));
}
if (jsElementWebpackConfigs && jsElementWebpackConfigs.length > 0) {
    allConfigs = allConfigs.concat(jsElementWebpackConfigs.filter(Boolean));
}

// NEUE FILTERLOGIK BASIEREND AUF process.env.FILTER_PATTERN
if (process.env.FILTER_PATTERN) {
    try {
        const regex = new RegExp(process.env.FILTER_PATTERN);
        
        const filteredConfigs = allConfigs.filter(conf => conf.name && regex.test(conf.name));

        if (filteredConfigs.length === 0) {
            console.warn(`Webpack: Keine Konfigurationen entsprachen dem Filter-Muster: "${process.env.FILTER_PATTERN}". Es wird nichts gebaut.`);
            module.exports = [];
        } else {
            console.log(`Webpack: ${filteredConfigs.length} von ${allConfigs.length} Konfigurationen werden gebaut basierend auf dem Muster "${process.env.FILTER_PATTERN}".`);
            filteredConfigs.forEach(conf => console.log(` - Verwendete Konfiguration: ${conf.name}, Ausgabe nach: ${conf.output ? path.relative(projectRoot, conf.output.path) : 'N/A'}`));
            module.exports = filteredConfigs;
        }
    } catch (e) {
        console.error(`Webpack: Ungültiges Regex-Muster im FILTER_PATTERN: "${process.env.FILTER_PATTERN}". Fehler: ${e.message}`);
        console.warn(`Webpack: Aufgrund des Fehlers im Regex werden alle ${allConfigs.length} Konfigurationen verwendet.`);
        allConfigs.forEach(conf => console.log(` - Konfigurationsname: ${conf.name}, Ausgabe nach: ${conf.output ? path.relative(projectRoot, conf.output.path) : 'N/A'}`));
        module.exports = allConfigs; // Fallback auf alle Konfigurationen bei Regex-Fehler
    }
} else if (allConfigs.length === 0) {
    console.warn("Keine Webpack-Konfigurationen zum Erstellen gefunden (FILTER_PATTERN nicht gesetzt).");
    module.exports = []; // Leeres Array exportieren, wenn überhaupt keine Konfigurationen vorhanden sind
} else {
    // Kein Filter, alle exportieren
    console.log(`Exportiere insgesamt ${allConfigs.length} Webpack-Konfigurationen (FILTER_PATTERN nicht gesetzt), inklusive aller CSS-Dateien.`);
    allConfigs.forEach(conf => console.log(` - Konfigurationsname: ${conf.name}, Ausgabe nach: ${conf.output ? path.relative(projectRoot, conf.output.path) : 'N/A'}`));
    module.exports = allConfigs;
}
