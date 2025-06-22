<?php

declare(strict_types=1);

/*
 * Caeli Area Check Bundle - German translations
 */

// Loading texts
$GLOBALS['TL_LANG']['caeli_area_check']['loading']['texts'] = [
    'checking_area' => 'Wir prüfen Ihre Fläche',
    'wind_conditions' => 'Passen die Windgegebenheiten?',
    'restrictions_check' => 'Gibt es Restriktionen?',
    'grid_connection' => 'Ist ein Netzanschluss gegeben?',
    'analyzing_potential' => 'Analysiere Windpotential',
    'checking_nature' => 'Prüfe Naturschutzgebiete',
    'calculating_economics' => 'Berechne Wirtschaftlichkeit',
    'checking_distances' => 'Überprüfe Abstandsregelungen',
    'analyzing_capacity' => 'Analysiere Netzkapazität',
    'evaluating_quality' => 'Bewerte Standortqualität',
];

// Form elements
$GLOBALS['TL_LANG']['caeli_area_check']['form'] = [
    'address_label' => 'Adresse oder Ort',
    'address_placeholder' => 'Postleitzahl eingeben',
    'address_alert' => 'Bitte geben Sie eine vollständige Postleitzahl ein.',
    'name_label' => 'Nachname',
    'firstname_label' => 'Vorname',
    'phone_label' => 'Telefon',
    'email_label' => 'E-Mail-Adresse',
    'submit_button' => 'Flächenprüfung starten',
    'button' => [
        'check_area' => 'Ergebnis anzeigen',
        'restart' => 'Neu starten',
    ],
    'warning' => 'Die ausgewählte Fläche ist größer als 700 Hektar. Bitte reduzieren Sie die Flächengröße.',
];

// Tutorial system
$GLOBALS['TL_LANG']['caeli_area_check']['tutorial'] = [
    'welcome' => [
        'title' => 'Willkommen bei Ihrem Flächencheck.',
        'content' => 'Ermitteln Sie in zwei Schritten das Windpotenzial Ihres Grundstücks. Klicken sie hierzu auf „Starten“.',
        'button_skip' => 'Überspringen',
        'button_next' => 'Starten',
    ],
    'plz_input' => [
        'title' => 'Schritt 1: Grundstück finden.',
        'button_back' => 'Zurück',
        'button_next' => 'Weiter',
        'content' => 'Geben Sie die Adresse Ihres Grundstücks ein und wählen Sie den vorgeschlagenen Ort aus.',
    ],
    'polygon_edit' => [
        'title' => 'Schritt 2: Grundstück einzeichnen und prüfen.',
        'content' => 'Sie sehen nun ein gelbes Polygon, dass Sie an die gewünschte Position verschieben können. Nutzen Sie die Eckpunkte des Polygons zur groben Eingrenzung Ihres Grundstücks und wählen Sie „Ergebnis anzeigen“, sobald sie fertig sind. Wir prüfen in Windeseile die Bedingungen Ihrer Fläche.',
        'button_back' => 'Zurück',
        'button_next' => 'Weiter',
    ],
    'area_confirm' => [
        'title' => 'Schritt 3: Fast geschafft!',
        'content' => 'Fast geschafft: Verschieben Sie das Polygon an die korrekte Position und richten Sie die Eckpunkte an Ihrem Grundstück aus. Klicken Sie dann auf „Ergebnis anzeigen“. Wir prüfen in Windeseile die Bedingungen auf Ihrer Fläche.',
        'button_back' => 'Zurück',
        'button_next' => 'Fertig',
    ],
];

// Consent overlay
$GLOBALS['TL_LANG']['caeli_area_check']['consent']['overlay'] = [
    'title' => 'Ihr Flächencheck <br>startet gleich!',
    'message' => 'Um Ihnen die interaktive Karte zeigen und Ihre Anfrage bearbeiten zu können, benötigen wir Ihr Einverständnis für notwendige Cookies.',
    'button' => 'Jetzt starten',
];

// Error messages
$GLOBALS['TL_LANG']['caeli_area_check']['error'] = [
    'no_geodata' => 'Keine Geodaten für diesen Ort gefunden!',
    'geocoding_failed' => 'Geocodierung war aus folgendem Grund nicht erfolgreich:',
    'select_area_first' => 'Bitte zuerst eine Fläche auswählen.',
    'google_maps_loading' => 'Google Maps API konnte nicht geladen werden!',
    'title' => 'Fehler',
    'retry_button' => 'Erneut versuchen',
];

// Alert messages for box display
$GLOBALS['TL_LANG']['caeli_area_check']['alerts'] = [
    'no_geodata' => [
        'title' => 'Keine Geodaten verfügbar',
        'message' => 'Keine Geodaten für diesen Ort gefunden!',
        'type' => 'danger',
    ],
    'select_area_first' => [
        'title' => 'Fläche auswählen',
        'message' => 'Bitte zuerst eine Fläche auswählen.',
        'type' => 'warning',
    ],
    'geocoding_failed' => [
        'title' => 'Geocodierung fehlgeschlagen',
        'message' => 'Geocodierung war aus folgendem Grund nicht erfolgreich:',
        'type' => 'danger',
    ],
    'google_maps_loading' => [
        'title' => 'Google Maps Fehler',
        'message' => 'Google Maps API konnte nicht geladen werden!',
        'type' => 'danger',
    ],
    'invalid_postal_code' => [
        'title' => 'Ungültige Postleitzahl',
        'message' => 'Bitte geben Sie eine gültige Adresse mit Postleitzahl ein.',
        'type' => 'warning',
    ],
    'ajax_fallback' => [
        'title' => 'Verarbeitung läuft',
        'message' => 'AJAX-Verarbeitung nicht verfügbar.',
        'type' => 'info',
    ],
    'processing_error' => [
        'title' => 'Verarbeitungsfehler',
        'message' => 'Bei der Flächenprüfung ist ein Fehler aufgetreten:',
        'type' => 'danger',
    ],
    'fallback_sync' => [
        'title' => 'Wechsel zu Standard-Verarbeitung',
        'message' => 'Die Prüfung wird mit der Standard-Verarbeitung fortgesetzt...',
        'type' => 'info',
    ],
];

// Loading overlay
$GLOBALS['TL_LANG']['caeli_area_check']['loading'] = [
    'title' => 'Ihr Grundstück wird geprüft...',
    'progress' => 'Fortschritt',
    'texts' => [
        'checking_area' => 'Wir prüfen Ihre Fläche',
        'wind_conditions' => 'Passen die Windgegebenheiten?',
        'restrictions_check' => 'Gibt es Restriktionen?',
        'grid_connection' => 'Ist ein Netzanschluss gegeben?',
        'analyzing_potential' => 'Analysiere Windpotential',
        'checking_nature' => 'Prüfe Naturschutzgebiete',
        'calculating_economics' => 'Berechne Wirtschaftlichkeit',
        'checking_distances' => 'Überprüfe Abstandsregelungen',
        'analyzing_capacity' => 'Analysiere Netzkapazität',
        'evaluating_quality' => 'Bewerte Standortqualität'
    ],
    'steps' => [
        'connecting' => 'Verbindung zur API herstellen...',
        'analyzing' => 'Fläche wird analysiert...',
        'rating' => 'Windpotential wird bewertet...',
        'fallback_rating' => 'Alternative Bewertung wird erstellt...',
        'saving' => 'Ergebnis wird gespeichert...',
        'completed' => 'Verarbeitung abgeschlossen!'
    ],
    'completed_redirect' => 'Abgeschlossen! Weiterleitung...'
];

// Interface elements
$GLOBALS['TL_LANG']['caeli_area_check']['interface'] = [
    'header' => [
        'title' => 'Caeli Wind Flächencheck',
        'subtitle' => 'für Windkraftanlagen',
    ],
    'form' => [
        'label' => 'Grundstück finden und einzeichnen:',
    ],
    'hints' => [
        'strong' => 'Hinweis:',
        'warning' => 'Warnung:',
    ],
    'no_plz_message' => 'Bitte geben Sie eine Adresse mit Postleitzahl ein.',
];

// Result page translations
$GLOBALS['TL_LANG']['caeli_area_check']['result'] = [
    'success' => [
        'title_bold' => 'Herzlichen Glückwunsch:',
        'title_text' => 'Ihr Grundstück weist gute Bedingungen für Windkraft auf.',
    ],
    'unsuitable' => [
        'title_bold' => 'Nicht ideal für Windkraft:',
        'title_text' => 'Gerne schaut sich unser Team Ihr Grundstück im Detail an.',
    ],
    'criteria' => [
        'wind_conditions' => [
            'title' => 'Windgegebenheiten',
            'description' => 'Die Windleistung im angegebenen Gebiet.',
            'rating' => [
                'green' => 'Gut geeignet',
                'yellow' => 'Bedingt geeignet',
                'red' => 'Nicht geeignet',
            ],
        ],
        'restrictions' => [
            'title' => 'Restriktionen',
            'description' => 'Prüfung raumordnerischer Restriktionen, auf denen Windkraftnutzung untersagt ist.',
            'rating' => [
                'green' => 'Keine Restriktionen',
                'yellow' => 'Eingeschränkt möglich',
                'red' => 'Restriktionen vorhanden',
            ],
        ],
        'grid_connection' => [
            'title' => 'Netzanschluss',
            'description' => 'Erreichbarkeit der Grundstücksfläche zur nächsten Hochspannung.',
            'rating' => [
                'green' => 'Gut erreichbar',
                'yellow' => 'Bedingt erreichbar',
                'red' => 'Nicht geeignet',
            ],
        ],
    ],
    'conclusion' => [
        'title' => 'Fazit:',
        'unsuitable_text' => 'Ihre Fläche erfüllt nicht alle Kriterien für ein optimales Windenergieprojekt.',
        'good_wind_text' => 'Die Windverhältnisse sind jedoch vielversprechend.',
        'contact_text' => 'Kontaktieren Sie uns für eine individuelle Beratung.',
    ],
    'form_section' => [
        'suitable_title' => 'Jetzt Ihre Windkraft-Chance prüfen – kostenlos und unverbindlich:',
        'unsuitable_title' => 'Jetzt gesicherte Aussage zur Windkrafteignung erhalten:',
    ],
    'error_states' => [
        'check_failed' => [
            'title' => 'Fehler bei der Auswertung',
            'button' => 'Neue Prüfung starten',
        ],
        'area_unsuitable' => [
            'title' => 'Ihr Flächencheck-Ergebnis',
            'warning_title' => 'Fläche nicht geeignet',
            'warning_text' => 'Leider ist Ihre gewählte Fläche für ein Windenergieprojekt nicht geeignet.',
            'reason_label' => 'Grund:',
        ],
        'checked_area' => [
            'title' => 'Geprüfte Fläche',
            'address_label' => 'Geprüfte Adresse:',
            'timestamp_label' => 'Prüfung durchgeführt:',
            'not_available' => 'Nicht verfügbar',
        ],
        'what_to_do' => [
            'title' => 'Was können Sie tun?',
            'try_other_area' => 'Probieren Sie eine andere Fläche aus',
            'contact_advice' => 'Kontaktieren Sie uns für eine individuelle Beratung',
            'alternative_locations' => 'Informieren Sie sich über alternative Standorte',
        ],
        'not_found' => [
            'title' => 'Fehler',
            'message' => 'Der angeforderte Flächencheck konnte nicht gefunden werden.',
            'button' => 'Neue Prüfung starten',
        ],
        'welcome' => [
            'title' => 'Willkommen zum Flächencheck',
            'message' => 'Bitte führen Sie zunächst eine Flächenprüfung durch.',
            'button' => 'Flächencheck starten',
        ],
    ],
    'buttons' => [
        'new_check' => 'Neue Fläche prüfen',
        'request_consultation' => 'Beratung anfragen',
    ],
];

// Status legend for area progress
$GLOBALS['TL_LANG']['caeli_area_check']['status'] = [
    'legend' => [
        'title' => 'Legende',
        'completed' => 'Abgeschlossen',
        'in_progress' => 'In Bearbeitung',
        'requested' => 'Angefragt',
    ],
]; 