<?php

$GLOBALS['TL_LANG']['tl_module']['auctionItemTemplate'] = ['Item-Template', 'Wählen Sie das Template für die Darstellung eines einzelnen Auktionseintrags.'];
$GLOBALS['TL_LANG']['tl_module']['auctionListingFilters'] = ['Auktions-Filter', 'Definieren Sie Filterregeln für die anzuzeigenden Auktionen. Jede Regel in einer neuen Zeile (z.B. bundesland = Bayern oder leistung_mw > 10). Unterstützte Operatoren: =, !=, <, >, <=, >=, IN, NOT IN, CONTAINS. Bei IN/NOT IN die Werte mit Komma trennen (z.B. status IN STARTED,FIRST_ROUND). Gültige Feldnamen können Sie z.B. über die Rohdaten-Vorschau eines Inhaltselements des Typs "Auktionselement" einsehen.'];
$GLOBALS['TL_LANG']['tl_module']['auctionSortRules'] = ['Sortierregeln (Modul)', 'Definieren Sie die Sortierreihenfolge der Auktionen für dieses Modul. Eine Regel pro Zeile im Format "Feldname sortierrichtung" (z.B. "leistung_mw asc" oder "countDown desc"). Die Regeln werden in der angegebenen Reihenfolge angewendet. Gültige Feldnamen können Sie z.B. über die Rohdaten-Vorschau eines Inhaltselements des Typs "Auktionselement" einsehen.'];
$GLOBALS['TL_LANG']['tl_module']['auctionSortBy'] = ['Sortieren nach Feld (veraltet für Modul)', 'Wählen Sie das Feld, nach dem die Auktionsliste sortiert werden soll. Bitte verwenden Sie stattdessen das neue Feld "Sortierregeln (Modul)".'];
$GLOBALS['TL_LANG']['tl_module']['auctionSortDirection'] = ['Sortierrichtung (veraltet für Modul)', 'Wählen Sie die Sortierrichtung. Bitte verwenden Sie stattdessen das neue Feld "Sortierregeln (Modul)".'];
$GLOBALS['TL_LANG']['tl_module']['perPage'] = ['Einträge pro Seite', 'Anzahl der Auktionen, die pro Seite angezeigt werden sollen (Standard: 12).'];

$GLOBALS['TL_LANG']['tl_module']['auctionRawDataPreviewMod_label'] = ['Vorschau Auktions-Rohdaten (Modul)', 'Zeigt eine Vorschau der Rohdaten einer Beispielauktion für dieses Modul. Diese Feldnamen können für die Filter- und Sortierdefinition verwendet werden. Das Feld selbst wird nicht gespeichert.'];

// Optionen für auctionSortBy (damit die Backend-Labels stimmen)
$GLOBALS['TL_LANG']['tl_module']['auctionSortBy_options'] = [
    'leistung_mw' => 'Leistung (MW)',
    'flaeche_ha' => 'Fläche (ha)',
    'countDown' => 'Countdown (Restlaufzeit)',
    'internalRateOfReturnBeforeRent' => 'IRR (vor Pacht)',
];

// Legenden
$GLOBALS['TL_LANG']['tl_module']['filter_legend'] = 'Filtereinstellungen';
$GLOBALS['TL_LANG']['tl_module']['sort_legend'] = 'Sortiereinstellungen'; 