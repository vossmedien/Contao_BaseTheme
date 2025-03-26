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
 * Fields
 */
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['title'] = ['Titel', 'Geben Sie einen aussagekräftigen Titel für die Konfiguration ein.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['newsArchive'] = ['News-Archiv', 'Wählen Sie das News-Archiv aus, in das die Artikel importiert werden sollen.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['serpApiKey'] = ['SerpAPI Schlüssel', 'Geben Sie Ihren SerpAPI API-Schlüssel ein.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['serpApiQuery'] = ['Suchbegriffe', 'Geben Sie hier die Begriffe ein, nach denen gesucht werden soll (z.B. "Windenergie Offshore Nordsee"). Mehrere Begriffe werden automatisch kombiniert.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['serpApiNumResults'] = ['Max. Ergebnisse', 'Maximale Anzahl an Ergebnissen (max. 100).'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['serpApiLocation'] = ['Standort', 'Standort für die Suche (z.B. "Germany").'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['serpApiLanguage'] = ['Sprache', 'Sprache für die Suchergebnisse.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['searchQuery'] = ['Suchanfrage', 'Geben Sie eine Suchanfrage für Google News ein.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['imageField'] = ['Bild-Feld', 'Wählen Sie das Feld im News-Modul, in das das Bild importiert werden soll.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['lastFetch'] = ['Letzter Abruf', 'Zeitpunkt des letzten Abrufs.'];

// Neue Filter-Optionen
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['dateRestrict'] = ['Zeitraum', 'Beschränken Sie die Ergebnisse auf einen bestimmten Zeitraum.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['newsSource'] = ['Quelle', 'Filtere nach einer bestimmten Nachrichtenquelle (z.B. "spiegel.de").'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['newsType'] = ['Nachrichtentyp', 'Wählen Sie einen bestimmten Typ von Nachrichtenartikeln aus.'];

// Pagination-Optionen
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['paginationEnabled'] = ['Pagination aktivieren', 'Aktivieren Sie die Paginierung, um mehr als 100 Ergebnisse abzurufen.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['maxPages'] = ['Max. Seiten', 'Maximale Anzahl an Seiten, die abgerufen werden sollen (max. 10).'];

// Keyword-Optionen
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['blacklistKeywords'] = ['Blockierte Keywords', 'Artikel, die eines dieser Keywords enthalten, werden ausgeblendet (ein Keyword pro Zeile).'];

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['title_legend'] = 'Titel und News-Archiv';
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['serpapi_legend'] = 'SerpAPI Einstellungen';
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['filter_legend'] = 'Filteroptionen';
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['keywords_legend'] = 'Keyword-Ausschlüsse';
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['preview_legend'] = 'Google News Vorschau';

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['new'] = ['Neue Konfiguration', 'Eine neue Google News Fetcher-Konfiguration erstellen'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['edit'] = ['Konfiguration bearbeiten', 'Konfiguration ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['copy'] = ['Konfiguration duplizieren', 'Konfiguration ID %s duplizieren'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['delete'] = ['Konfiguration löschen', 'Konfiguration ID %s löschen'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['show'] = ['Konfiguration anzeigen', 'Konfiguration ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['fetch'] = ['News abrufen', 'News für Konfiguration ID %s abrufen und vorschauen'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['processed'] = ['Verarbeitete Artikel', 'Bereits verarbeitete Artikel für Konfiguration ID %s anzeigen'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['fetchNewsButton'] = ['Google News abrufen', 'Ruft Google News basierend auf der konfigurierten Suchanfrage ab.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['previewView'] = ['Vorschau', 'Zeigt eine Vorschau der abgerufenen Google News an.'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['previewItems'] = ['Vorschau-Elemente', 'Vorschau der abgerufenen News-Artikel'];
$GLOBALS['TL_LANG']['tl_caeli_googlenews']['publishNews'] = ['Ausgewählte News veröffentlichen', 'Veröffentlicht die ausgewählten News-Artikel.']; 