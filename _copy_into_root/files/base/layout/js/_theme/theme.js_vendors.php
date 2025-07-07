<?php
// files/base/layout/js/_theme/theme.js_vendors.php
return [
  [ 'src' => 'js-cookie/dist/js.cookie.min.js', 'attributes' => '|static' ], // Immer benötigt für Cookies
  [ 'src' => 'bootstrap/dist/js/bootstrap.bundle.min.js', 'attributes' => '|defer|static' ], // Immer benötigt für Tooltips/Modals
  [ 'src' => 'venobox/dist/venobox.min.js', 'attributes' => '|defer' ], // Entfernt |static - wird bedingt geladen
  //[ 'src' => '@popperjs/core/dist/umd/popper.min.js', 'attributes' => '|defer|static' ],
  [ 'src' => 'swiper/swiper-bundle.min.js', 'attributes' => '|defer' ], // Entfernt |static - wird bedingt geladen
  [ 'src' => 'macy/dist/macy.js', 'attributes' => '|defer|static' ], // Bleibt static - relativ klein
  [ 'src' => 'mmenu-light/dist/mmenu-light.js', 'attributes' => '|defer|static' ] // Bleibt static - Mobile Navigation
]; 