<?php

/*
 * VSM Helper Tools Bundle for Contao Open Source CMS.
 */

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['session_id'] = ['Session-ID', 'Eindeutige Session-ID der Zahlungssitzung.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['status'] = ['Status', 'Status der Zahlungssitzung.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['customer_data'] = ['Kundendaten', 'Persönliche Daten des Kunden.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['product_data'] = ['Produktdaten', 'Informationen zum gekauften Produkt.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['payment_data'] = ['Zahlungsdaten', 'Informationen zur Zahlung.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['download_link'] = ['Download-Link', 'Link zum Herunterladen der Datei.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['download_token'] = ['Download-Token', 'Eindeutiger Token für den Datei-Download.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['download_expires'] = ['Download gültig bis', 'Zeitpunkt, bis zu dem der Download gültig ist.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['download_limit'] = ['Download-Limit', 'Maximale Anzahl an Downloads.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['download_count'] = ['Download-Zähler', 'Anzahl der bisherigen Downloads.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['user_id'] = ['Benutzer-ID', 'ID des erstellten Benutzers.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['subscription_duration'] = ['Abo-Laufzeit', 'Laufzeit des Abonnements in Tagen.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['created_at'] = ['Erstellt am', 'Erstellungsdatum der Zahlungssitzung.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['updated_at'] = ['Aktualisiert am', 'Zeitpunkt der letzten Aktualisierung.'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['emails_sent'] = ['E-Mails gesendet', 'Bestätigungs-E-Mails wurden gesendet.'];

/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['session_legend'] = 'Sitzungsdetails';
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['customer_legend'] = 'Kundeninformationen';
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['product_legend'] = 'Produktinformationen';
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['payment_legend'] = 'Zahlungsinformationen';
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['download_legend'] = 'Download-Einstellungen';
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['user_legend'] = 'Benutzerkonto';
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['system_legend'] = 'Systemeinstellungen';

/**
 * Options
 */
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['status_options'] = [
    'pending' => 'Ausstehend',
    'completed' => 'Abgeschlossen',
    'failed' => 'Fehlgeschlagen',
    'canceled' => 'Abgebrochen',
];

/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['new'] = ['Neue Zahlungssitzung', 'Eine neue Zahlungssitzung erstellen'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['edit'] = ['Zahlungssitzung bearbeiten', 'Zahlungssitzung ID %s bearbeiten'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['delete'] = ['Zahlungssitzung löschen', 'Zahlungssitzung ID %s löschen'];
$GLOBALS['TL_LANG']['tl_stripe_payment_sessions']['show'] = ['Zahlungssitzung anzeigen', 'Details der Zahlungssitzung ID %s anzeigen']; 