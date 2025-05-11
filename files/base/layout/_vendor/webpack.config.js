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
        name: `${themeNameClean}-app`,
        mode: 'production',
        entry: appEntryFiles,
        output: {
            filename: `${themeNameClean}.bundle.min.js`,
            path: themeSpecificDistPath,
            publicPath: themeSpecificPublicPath,
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
        ]
    };

    // Gebe nur die appBundleConfig für dieses Theme zurück
    return [appBundleConfig]; // Muss immer noch ein Array sein, da flatMap es erwartet
});

// --- SCSS-Konfigurationen ---
// Alte Logik für mainScssFiles wird entfernt.
// const mainScssFiles = glob.sync(path.join(cssWorkspaceBase, '*.scss').replace(/\\/g, '/'))
// .filter(file => !path.basename(file).startsWith('_'));
// console.log('Gefundene Haupt-SCSS-Dateien:', mainScssFiles);

const cssThemeFolders = fs.readdirSync(cssWorkspaceBase)
    .map(item => ({ name: item, path: path.join(cssWorkspaceBase, item) }))
    .filter(item => {
        const stat = fs.statSync(item.path);
        // Nur Ordner, die mit '_' beginnen und nicht '_dist-manifest' sind.
        // Und stelle sicher, dass es wirklich ein Ordner ist.
        return stat.isDirectory() && item.name.startsWith('_') && item.name !== '_dist-manifest';
    });

console.log('Gefundene CSS-Theme-Ordner:', cssThemeFolders.map(f => f.name));

const cssWebpackConfigs = cssThemeFolders.flatMap(themeFolder => {
    const themeNameRaw = themeFolder.name; // z.B. _caeliRelaunch
    const themeNameClean = themeNameRaw.substring(1); // z.B. caeliRelaunch
    const themeCssDir = themeFolder.path; // z.B. /path/to/files/base/layout/css/_caeliRelaunch
    const themeCssDistDir = path.join(themeCssDir, 'dist');
    const themePublicPath = `/files/base/layout/css/${themeNameRaw}/dist/`;
    const themeManifestDir = path.join(manifestBasePath, themeNameRaw); // Manifeste im Ordner _caeliRelaunch etc.

    // Sicherstellen, dass die Verzeichnisse existieren
    if (!fs.existsSync(themeCssDistDir)) {
        fs.mkdirSync(themeCssDistDir, { recursive: true });
        console.log(`   Erstelle themenspezifisches CSS dist-Verzeichnis: ${path.relative(projectRoot, themeCssDistDir)}`);
    }
    if (!fs.existsSync(themeManifestDir)) {
        fs.mkdirSync(themeManifestDir, { recursive: true });
        console.log(`   Erstelle themenspezifisches Manifest-Verzeichnis für CSS: ${path.relative(projectRoot, themeManifestDir)}`);
    }

    const scssFilesToBundle = [
        '_vendors.scss',
        '_base.scss',
        '_theme.scss',
        '_root-variables.scss',
        '_fonts.scss'
    ];

    let themeAliases = {};
    // const themeAliasConfigFile = path.join(themeCssDir, 'theme.fontaliases.js');
    // if (fs.existsSync(themeAliasConfigFile)) {
    //     try {
    //         const loadedAliasesRelative = require(themeAliasConfigFile);
    //         for (const key in loadedAliasesRelative) {
    //             themeAliases[key] = path.resolve(nodeModulesPath, loadedAliasesRelative[key]);
    //         }
    //         console.log(`   Lade Font-Aliase aus ${path.relative(projectRoot, themeAliasConfigFile)} für Theme ${themeNameRaw}`);
    //     } catch (e) {
    //         console.error(`   Fehler beim Laden von ${themeAliasConfigFile} für Theme ${themeNameRaw}:`, e);
    //     }
    // } else {
    //     console.log(`   Keine theme.fontaliases.js für Theme "${themeNameRaw}" gefunden in ${path.relative(projectRoot, themeCssDir)}`);
    // }

    return scssFilesToBundle.map(scssFileName => {
        const scssFilePath = path.join(themeCssDir, scssFileName);
        if (!fs.existsSync(scssFilePath)) {
            console.warn(`   SCSS-Datei ${scssFileName} nicht in ${themeNameRaw} gefunden unter ${path.relative(projectRoot, scssFilePath)}. Überspringe.`);
            return null; // Überspringen, wenn die Datei nicht existiert
        }

        const entryName = scssFileName.replace('.scss', ''); // z.B. _vendors, _base

        console.log(`CSS-Konfig für Theme "${themeNameRaw}", Datei "${scssFileName}":`);
        console.log(`   SCSS-Datei: ${path.relative(projectRoot, scssFilePath)}`);
        console.log(`   Ausgabe (output.path): ${path.relative(projectRoot, themeCssDistDir)}`);
        console.log(`   Public Path (output.publicPath): ${themePublicPath}`);
        console.log(`   Bundle-Name (entryName): ${entryName}`);

        return {
            name: `${themeNameClean}-${entryName}-css`, // Eindeutiger Name für die Webpack-Konfig z.B. caeliRelaunch-_vendors-css
            mode: 'production',
            entry: {
                [entryName]: scssFilePath // Key ist der Bundle-Name, Value der Pfad zur Datei
            },
            output: {
                path: themeCssDistDir,
                publicPath: themePublicPath,
                assetModuleFilename: 'fonts/[name].[hash][ext][query]', // Name der Fontdatei beibehalten
                clean: false, // Wichtig, da wir mehrere Bundles in denselben Ordner schreiben könnten
            },
            module: {
                rules: [
                    {
                        test: /\.scss$/,
                        use: [
                            {
                                loader: MiniCssExtractPlugin.loader,
                            },
                            {
                                loader: 'css-loader',
                                options: {
                                    sourceMap: true, // Wichtig für resolve-url-loader
                                }
                            },
                            {
                                loader: 'resolve-url-loader', // NEU
                                options: {
                                    sourceMap: true, // Wichtig, benötigt sourcemaps vom vorherigen Loader (sass-loader)
                                    removeCR: true, // Kann bei Windows-Zeilenumbrüchen helfen
                                }
                            },
                            {
                                loader: 'sass-loader',
                                options: {
                                    sourceMap: true, // Wichtig für resolve-url-loader
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
                new RemoveEmptyScriptsPlugin(),
                new MiniCssExtractPlugin({
                    filename: '[name].bundle.min.css', // Wird zu z.B. _vendors.bundle.min.css
                }),
                new WebpackManifestPlugin({
                    fileName: path.join(themeManifestDir, `${entryName}-css.manifest.json`), // separates Manifest pro Bundle
                    publicPath: themePublicPath,
                    filter: (file) => !file.name.endsWith('.js') && file.name.startsWith(entryName) && file.name.endsWith('.css'),
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
    }).filter(Boolean); // Entferne null-Werte, falls Dateien nicht gefunden wurden
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
