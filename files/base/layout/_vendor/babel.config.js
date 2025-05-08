// /Users/christian.voss/PhpstormProjects/Caeli-Relaunch/files/base/layout/_vendor/babel.config.js
module.exports = {
  presets: [
    [
      '@babel/preset-env',
      {
        targets: '> 0.25%, not dead', // Definiert, welche Browser du unterstützen möchtest
        modules: 'commonjs'
      },
    ],
  ],
};