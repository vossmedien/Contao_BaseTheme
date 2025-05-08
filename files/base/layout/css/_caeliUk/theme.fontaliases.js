    // files/base/layout/css/_caeli_uk/theme.fontaliases.js
    module.exports = {
      // Schlüssel: Das problematische Pfadsegment, das Webpack/css-loader versucht aufzulösen
      // Dieser Schlüssel muss exakt dem Pfadteil aus der Fehlermeldung entsprechen,
      // den css-loader zu finden versucht, *bevor* der eigentliche Dateiname kommt.
      '/files/base/layout/_vendor/node_modules/@awesome.me/kit-c9b4e661cb/icons/webfonts':
        // Wert: Der korrekte Pfad zum Verzeichnis, relativ zum node_modules Ordner
        // (nodeModulesPath ist /files/base/layout/_vendor/node_modules/)
        '@awesome.me/kit-c9b4e661cb/icons/webfonts',

      // Falls _caeli_uk noch andere problematische Font-Pfade hat, hier hinzufügen.
      // Wenn _caeli_uk das kit-2504132cb2 NICHT verwendet, brauchst du dessen Alias hier auch nicht.
    };