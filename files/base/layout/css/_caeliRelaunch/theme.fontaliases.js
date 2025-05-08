// files/base/layout/css/_caeli_relaunch/theme.fontaliases.js
module.exports = {
  // Schlüssel: Das problematische Pfadsegment, das Webpack/css-loader versucht aufzulösen
  '/files/base/layout/_vendor/node_modules/@awesome.me/kit-2504132cb2/icons/webfonts':
    // Wert: Der korrekte Pfad zum Verzeichnis, relativ zum node_modules Ordner
    '@awesome.me/kit-2504132cb2/icons/webfonts',

  // Füge hier weitere themenspezifische Aliase hinzu, falls nötig
  // Beispiel:
  // '/some/other/problem/path': 'some-other-package/dist/assets'
};