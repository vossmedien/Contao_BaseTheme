<?php

/*
 * This file is part of Caeli Google News Fetcher.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-google-news-fetch
 */

/**
 * Miscellaneous
 */
$GLOBALS['TL_LANG']['MSC']['googleNewsFetcherPreview'] = 'Google News Vorschau';
$GLOBALS['TL_LANG']['MSC']['noNewsItemsFound'] = 'Keine Nachrichtenartikel gefunden.';
$GLOBALS['TL_LANG']['MSC']['back'] = 'Zurück';
$GLOBALS['TL_LANG']['MSC']['selectNewsToImport'] = 'Wählen Sie die zu importierenden Nachrichtenartikel aus:';
$GLOBALS['TL_LANG']['MSC']['visitSource'] = 'Quelle besuchen';
$GLOBALS['TL_LANG']['MSC']['importSelected'] = 'Ausgewählte importieren';
$GLOBALS['TL_LANG']['MSC']['noItemsSelected'] = 'Keine Artikel ausgewählt.';
$GLOBALS['TL_LANG']['MSC']['configNotFound'] = 'Konfiguration nicht gefunden.';
$GLOBALS['TL_LANG']['MSC']['newsArchiveNotFound'] = 'News-Archiv nicht gefunden.';
$GLOBALS['TL_LANG']['MSC']['newsImported'] = '%s Nachrichtenartikel wurden erfolgreich importiert.';

// Verarbeitete Artikel
$GLOBALS['TL_LANG']['MSC']['googleNewsFetcherProcessed'] = 'Bereits verarbeitete Google News Artikel';
$GLOBALS['TL_LANG']['MSC']['noProcessedNewsItemsFound'] = 'Keine verarbeiteten Nachrichtenartikel gefunden.';
$GLOBALS['TL_LANG']['MSC']['processedNewsItems'] = 'Bereits verarbeitete Artikel:';
$GLOBALS['TL_LANG']['MSC']['resetProcessed'] = 'Verarbeitete Artikel zurücksetzen';
$GLOBALS['TL_LANG']['MSC']['resetProcessedConfirm'] = 'Wollen Sie wirklich alle verarbeiteten Artikel zurücksetzen? Die Artikel bleiben im News-Archiv bestehen.';
$GLOBALS['TL_LANG']['MSC']['processedArticlesReset'] = 'Die Liste der verarbeiteten Artikel wurde zurückgesetzt.';
$GLOBALS['TL_LANG']['MSC']['accepted'] = 'Importiert';
$GLOBALS['TL_LANG']['MSC']['rejected'] = 'Übersprungen';

/**
 * Content elements
 */
$GLOBALS['TL_LANG']['CTE']['caeli_googlenews'] = ['Google News Fetcher', 'Google News abrufen und importieren'];

/**
 * Errors
 */
$GLOBALS['TL_LANG']['ERR']['noApiKeyProvided'] = 'Bitte geben Sie einen SerpAPI-Schlüssel ein.';
$GLOBALS['TL_LANG']['ERR']['noQueryProvided'] = 'Bitte geben Sie eine Suchanfrage ein.';
$GLOBALS['TL_LANG']['ERR']['noNewsArchiveSelected'] = 'Bitte wählen Sie ein News-Archiv aus.';
$GLOBALS['TL_LANG']['ERR']['invalidSerpApiKey'] = 'Der SerpAPI-Schlüssel ist ungültig.';
$GLOBALS['TL_LANG']['ERR']['serpApiError'] = 'Fehler bei der Anfrage an SerpAPI: %s';
$GLOBALS['TL_LANG']['ERR']['noNewsFound'] = 'Es wurden keine News gefunden.';
$GLOBALS['TL_LANG']['ERR']['noNewsArchiveFound'] = 'Das ausgewählte News-Archiv existiert nicht mehr.';
$GLOBALS['TL_LANG']['ERR']['cannotSaveToJsonFile'] = 'Die News konnten nicht in der JSON-Datei gespeichert werden.';
$GLOBALS['TL_LANG']['ERR']['cannotCreateJsonDir'] = 'Das Verzeichnis für die JSON-Dateien konnte nicht erstellt werden.';
$GLOBALS['TL_LANG']['ERR']['invalidImageUrl'] = 'Die Bild-URL ist ungültig.';
$GLOBALS['TL_LANG']['ERR']['cannotDownloadImage'] = 'Das Bild konnte nicht heruntergeladen werden.';
$GLOBALS['TL_LANG']['ERR']['invalidImageFormat'] = 'Das Bildformat wird nicht unterstützt.';
$GLOBALS['TL_LANG']['ERR']['invalidNewsIndex'] = 'Der angegebene News-Index existiert nicht.'; 