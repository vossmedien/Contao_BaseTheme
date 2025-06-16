<?php

$GLOBALS['TL_LANG']['tl_content']['auction_ids'] = ['Auktionen', 'Wählen Sie die Auktionen aus, die angezeigt werden sollen.'];
$GLOBALS['TL_LANG']['tl_content']['auctionElementFilters'] = ['Auktions-Filter', 'Definieren Sie Filterregeln für die anzuzeigenden Auktionen. Jede Regel in einer neuen Zeile (z.B. bundesland = Bayern oder leistung_mw > 10). Unterstützte Operatoren: =, !=, <, >, <=, >=, IN, NOT IN, CONTAINS. Bei IN/NOT IN die Werte mit Komma trennen (z.B. status IN STARTED,FIRST_ROUND).'];
$GLOBALS['TL_LANG']['tl_content']['auctionRawDataPreview_label'] = ['Vorschau Auktions-Rohdaten', 'Zeigt eine Vorschau der Rohdaten einer Beispielauktion. Diese Feldnamen können für die Filterdefinition verwendet werden. Das Feld selbst wird nicht gespeichert.'];

// Sortierfelder für Inhaltselement
$GLOBALS['TL_LANG']['tl_content']['auctionSortByCE'] = ['Sortieren nach Feld (veraltet für CE)', 'Wählen Sie das Feld, nach dem die Auktionen sortiert werden sollen. Bitte verwenden Sie das Feld "Sortierregeln (Inhaltselement)".'];
$GLOBALS['TL_LANG']['tl_content']['auctionSortDirectionCE'] = ['Sortierrichtung (veraltet für CE)', 'Wählen Sie die Sortierrichtung. Bitte verwenden Sie das Feld "Sortierregeln (Inhaltselement)".'];
$GLOBALS['TL_LANG']['tl_content']['auctionSortRulesCE'] = ['Sortierregeln (Inhaltselement)', 'Definieren Sie die Sortierreihenfolge der Auktionen für dieses Inhaltselement. Eine Regel pro Zeile im Format "Feldname sortierrichtung" (z.B. "leistung_mw asc" oder "countDown desc"). Die Regeln werden in der angegebenen Reihenfolge angewendet. Gültige Feldnamen sehen Sie in der Rohdaten-Vorschau.'];
$GLOBALS['TL_LANG']['tl_content']['auctionSortByCE_options'] = [
    'leistung_mw' => 'Leistung (MW)',
    'flaeche_ha' => 'Fläche (ha)',
    'countDown' => 'Countdown (Restlaufzeit)',
    'internalRateOfReturnBeforeRent' => 'IRR (vor Pacht)',
];

$GLOBALS['TL_LANG']['tl_content']['auctionItemTemplateCE'] = ['Item-Template (Inhaltselement)', 'Wählen Sie das Template für die Darstellung eines einzelnen Auktionseintrags in diesem Inhaltselement. Wenn leer, wird ein Standard-Template verwendet.'];

$GLOBALS['TL_LANG']['tl_content']['auctionApiUrlParamsCE'] = ['API URL Parameter', 'Zusätzliche Parameter für die API-URL (z.B. "/closed?language=de"). Wird an die Base-URL aus der Konfiguration angehängt.'];

// Legenden
$GLOBALS['TL_LANG']['tl_content']['api_legend'] = 'API-Einstellungen';
$GLOBALS['TL_LANG']['tl_content']['sort_legend_ce'] = 'Sortiereinstellungen für Inhaltselement'; 