<?php

declare(strict_types=1);

/*
 * Caeli Area PDF Bundle - German translations
 */

// PDF Content - Main Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['pdf'] = [
    'document_title' => 'Unverbindliche Ersteinschätzung Ihres Grundstücks',
    'document_subject' => 'Flächenverpachtung für Windenergie',
    'main_title_1' => 'Unverbindliche Ersteinschätzung',
    'main_title_2' => 'Ihres Grundstücks',
    'subtitle' => 'Ihr erster Schritt zur erfolgreichen Flächenverpachtung für Windenergie',
    'map_placeholder' => 'Einbindung Karte',
    'property_data_title' => 'Ihre Flächendaten',
    'municipality' => 'Gemeinde',
    'district' => 'Landkreis',
    'area_size' => 'Flächengröße',
    'hectares' => 'ha',
    'geo_id' => 'Geo ID des Windparks',
    'created_for' => 'Erstellt von Caeli Wind für',
    'date_format' => 'd.m.Y - H:i'
];

// PDF Content - Results Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['results'] = [
    'result_title' => 'Ergebnis der Ersteinschätzung:',
    'result_subtitle' => 'Gute Bedingungen mit wirtschaftlichem Potenzial!',
    'congratulations' => 'Herzlichen Glückwunsch,',
    'recommendation' => 'Ihr Standort eignet sich voraussichtlich für eine wirtschaftliche Nutzung als Windenergiefläche. Wir empfehlen Ihnen eine detaillierte Analyse durch unser Expertenteam.',
    'wind_conditions_title' => 'Windgegebenheiten',
    'wind_conditions_text' => 'Am angegebenen Standort liegen mit einer Windleistungsdichte von        %s W/m² bis %s W/m² gute Windbedingungen vor.',
    'restrictions_title' => 'Restriktionen',
    'restrictions_text' => 'Voraussichtlich bis zu %s%% der Grundstücksfläche können unter Berücksichtigung raumordnerischen Restriktionen für die Entwicklung eines Windparks genutzt werden.',
    'grid_connection_title' => 'Netzanschluss',
    'grid_connection_text' => 'Für die Netzanbindung an das Hochspannungsnetz sind zwischen %s m und %s m Leitungsbau erforderlich.',
    'disclaimer' => 'Hinweis: Diese Einschätzung basiert auf groben Angaben und ersetzt keine detaillierte Analyse mittels genauer Einzeichnung auf einer Karte. Dies erfolgt in einem persönlichen Gespräch und durch die spätere Bereitstellung Ihrer genauen Grundstücksdaten.',
    'copyright' => '© Caeli Wind GmbH',
    'imprint' => 'Impressum'
];

// PDF Content - Steps Page
$GLOBALS['TL_LANG']['caeli_area_pdf']['steps'] = [
    'title' => 'So geht es weiter...',
    'subtitle_1' => 'Von der unverbindlichen Ersteinschätzung bis zum',
    'subtitle_2' => ' Vertragsabschluss – für Sie 100% kostenlos!',
    'step_1_title' => 'Schritt 1: Persönliches Gespräch & Experteneinschätzung',
    'step_1_text' => 'Am angegebenen Standort liegen mit einer Windleistungsdichte von 400 W/m² bis 510 W/m² gute Windbedingungen vor. In einem virtuellen Gesprächstermin stellen wir Ihnen unsere Leistungen vor und führen mit Ihnen eine genauere Bewertung Ihres Grundstücks mittels Einzeichnung auf einer Karte durch.',
    'step_2_title' => 'Schritt 2: Gemeinsame Planung & Zielsetzung',
    'step_2_text' => 'Sie erhalten die Analyseergebnisse zu Ihrer Fläche, notwendige Information sowie verbindliche Antworten auf Ihre Fragen. Gemeinsam klären wir die nächsten Schritte, um Ihre Potenzialfläche für Windenergie unkompliziert und sicher zu vermarkten – ganz nach Ihren persönlichen Vorstellungen.',
    'step_3_title' => 'Schritt 3: Zugang zum Marktplatz & erfolgreiche Vermittlung',
    'step_3_text' => 'Im Zuge der professionellen Vermarktung Ihres Grundstücks erhalten Sie exklusiven Zugang zu unserem Online-Marktplatz für Windenergieflächen. Dank der großen Anzahl renommierter Projekt-Entwickler finden wir gemeinsam den richtigen Partner für Ihr Windparkprojekt. Im gesamten Prozess begleiten wir Sie Schritt für Schritt – von der ersten Beratung bis zur Endverhandlung.',
    'property_data_title' => 'Ihre Flächendaten'
];

// API related
$GLOBALS['TL_LANG']['caeli_area_pdf']['api'] = [
    'map_load_error' => 'Kartenbild konnte nicht geladen werden!'
];

// Backend messages
$GLOBALS['TL_LANG']['caeli_area_pdf']['backend'] = [
    'info_message' => 'Dieses Modul generiert PDF-Berichte für Windenergie-Flächenbewertungen. Es ist nur im Frontend aktiv wenn ein parkid Parameter vorhanden ist.'
]; 