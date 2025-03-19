<?php

declare(strict_types=1);

// Seiteneinstellungen
$GLOBALS['TL_LANG']['tl_page']['pin_protected_legend'] = 'PIN-Schutz Einstellungen';
$GLOBALS['TL_LANG']['tl_page']['pin_protected'] = ['Seite mit PIN schützen', 'Aktivieren Sie diese Option, um den Zugriff auf diese Seite durch einen PIN zu schützen.'];
$GLOBALS['TL_LANG']['tl_page']['pin_value'] = ['PIN-Wert', 'Geben Sie den PIN ein, der für den Zugriff auf diese Seite erforderlich ist.'];
$GLOBALS['TL_LANG']['tl_page']['pin_login_page'] = ['PIN-Login-Seite', 'Wählen Sie die Seite aus, auf der das PIN-Login-Formular angezeigt wird.'];
$GLOBALS['TL_LANG']['tl_page']['pin_timeout'] = ['Timeout in Sekunden', 'Geben Sie die Zeit in Sekunden ein, nach der eine PIN-Autorisierung abläuft. Standard: 1800 (30 Minuten)'];

// PIN-Login Formular
$GLOBALS['TL_LANG']['PIN_LOGIN']['form_title'] = 'PIN-Eingabe erforderlich';
$GLOBALS['TL_LANG']['PIN_LOGIN']['pin_label'] = 'PIN-Code';
$GLOBALS['TL_LANG']['PIN_LOGIN']['email_label'] = 'E-Mail-Adresse';
$GLOBALS['TL_LANG']['PIN_LOGIN']['extra_data_label'] = 'Zusätzliche Informationen';
$GLOBALS['TL_LANG']['PIN_LOGIN']['submit_button'] = 'Login';

// Fehlermeldungen
$GLOBALS['TL_LANG']['PIN_LOGIN']['error_no_pin'] = 'Bitte geben Sie einen PIN-Code ein.';
$GLOBALS['TL_LANG']['PIN_LOGIN']['error_no_email'] = 'Bitte geben Sie eine E-Mail-Adresse ein.';
$GLOBALS['TL_LANG']['PIN_LOGIN']['error_invalid_pin'] = 'Der eingegebene PIN ist nicht korrekt.';
$GLOBALS['TL_LANG']['PIN_LOGIN']['success_message'] = 'PIN korrekt, Sie werden weitergeleitet...';

// FE Modul
$GLOBALS['TL_LANG']['FMD']['pin_login'] = ['PIN-Login Formular', 'Zeigt ein Formular zur PIN-Eingabe an']; 