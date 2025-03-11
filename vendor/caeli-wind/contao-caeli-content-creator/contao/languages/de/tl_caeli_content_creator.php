<?php

declare(strict_types=1);

/*
 * This file is part of Caeli KI Content-Creator.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/contao-caeli-content-creator
 */

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['api_legend'] = 'API-Konfiguration';
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['content_generation_legend'] = 'Inhalts-Generierung';
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['advanced_settings_legend'] = 'Erweiterte Einstellungen';

/**
 * Übersetzungen für tl_caeli_content_creator
 */
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['title_legend'] = 'Titel und Inhalt';
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['content_legend'] = 'Content-Einstellungen';
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['preview_legend'] = 'Vorschau und Generierung';

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['title'] = ['Titel', 'Titel des Content-Creator-Eintrags.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['grokApiKey'] = ['KI API-Schlüssel', 'Geben Sie Ihren API-Schlüssel für die KI-Service-Integration ein.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['grokApiEndpoint'] = ['KI API-Endpunkt', 'Geben Sie den Endpunkt des KI-Services ein (z.B. https://api.groq.com/openai/v1).'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['newsArchive'] = ['Nachrichtenarchiv', 'Das Nachrichtenarchiv, in dem der generierte Inhalt gespeichert werden soll.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['contentElement'] = ['Inhaltselement', 'Der Typ des Inhaltselements, der für den generierten Inhalt verwendet werden soll.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['topic'] = ['Thema', 'Das Thema des zu generierenden Inhalts.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['targetAudience'] = ['Zielgruppe', 'Die Zielgruppe des zu generierenden Inhalts.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['emphasis'] = ['Schwerpunkt', 'Der Schwerpunkt des zu generierenden Inhalts.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['min_words'] = ['Mindestanzahl Wörter', 'Die Mindestanzahl an Wörtern für den zu generierenden Inhalt.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['include_sources'] = ['Quellenangaben einfügen', 'Fügt Quellenangaben am Ende des Artikels ein.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['add_target_blank'] = ['Links in neuem Tab öffnen', 'Fügt target="_blank" zu allen externen Links hinzu.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['tags'] = ['Tags', 'Geben Sie Tags für den Beitrag ein (kommagetrennt).'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['addSubpalette'] = ['Erweiterte Anweisungen hinzufügen', 'Fügen Sie erweiterte Anweisungen für die KI hinzu.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['additionalInstructions'] = ['Zusätzliche Anweisungen', 'Zusätzliche Anweisungen für die KI.'];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'] = ['Inhalt generieren', 'Generiert einen neuen Inhalt basierend auf den Einstellungen.'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['previewView'] = ['Vorschau', 'Zeigt eine Vorschau des generierten Inhalts an.'];

// Operationsbezeichnungen
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['edit'] = ['Bearbeiten', 'Eintrag ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['delete'] = ['Löschen', 'Eintrag ID %s löschen'];
$GLOBALS['TL_LANG']['tl_caeli_content_creator']['show'] = ['Details anzeigen', 'Details des Eintrags ID %s anzeigen']; 