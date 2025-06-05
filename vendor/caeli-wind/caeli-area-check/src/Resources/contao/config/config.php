<?php

// Frontend-Modul registrieren
$GLOBALS['FE_MOD']['caeli_wind_modules']['caeli_area_check'] = \CaeliWind\CaeliAreaCheckBundle\Module\AreaCheckModule::class;
$GLOBALS['BE_MOD']['content']['flaechencheck'] = [
    'tables' => ['tl_flaechencheck'],
]; 
// Hier können Contao-spezifische Konfigurationen stehen
// Routing für AJAX-Endpoint erfolgt in routing.yaml 