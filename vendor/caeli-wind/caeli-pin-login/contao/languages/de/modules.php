<?php

/*
 * This file is part of Caeli PIN-Login.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-pin-login
 */

/**
 * Backend modules
 */
$GLOBALS['TL_LANG']['MOD']['caeli'] = 'Caeli Wind';
$GLOBALS['TL_LANG']['MOD']['pin_logins'] = ['PIN-Logins', 'Alle erfolgreichen PIN-Logins verwalten'];

/**
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['caeli_pin_login'] = ['PIN-Login', 'Ein Login-Modul mit PIN-Abfrage'];

/**
 * Feld-Beschreibungen
 */
$GLOBALS['TL_LANG']['tl_module']['requireEmail'] = ['E-Mail-Adresse erforderlich', 'Fordert bei der PIN-Eingabe auch eine E-Mail-Adresse an.'];
$GLOBALS['TL_LANG']['tl_module']['requireEmail_info'] = 'Bei Aktivierung wird im Formular ein Pflichtfeld für E-Mail-Adressen angezeigt.';
$GLOBALS['TL_LANG']['tl_module']['extraDataField'] = ['Zusätzliches Datenfeld anzeigen', 'Zeigt ein weiteres Eingabefeld für zusätzliche Daten an.'];
$GLOBALS['TL_LANG']['tl_module']['extraDataField_info'] = 'Bei Aktivierung wird ein optionales Feld für zusätzliche Informationen angezeigt.'; 