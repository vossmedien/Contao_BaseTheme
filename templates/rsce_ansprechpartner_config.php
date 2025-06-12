<?php


use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Contao\System;

$config = array(
    'label' => array('Custom | Ansprechpartner mit Kontaktinformationen', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        // --- 2. Allgemeines Layout & Raster --- //
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation (Überschrift)', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'clr') // clr hinzugefügt für eigene Zeile
        ),
        // --- 1. Allgemeine Text- & Inhaltselemente --- //
        'topline' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),
        'contact_text' => array(
            'label' => array('Kontakttext', 'Text neben den Ansprechpartnern'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),


        'partner_columns' => array(
            'label' => array('Anzahl Spalten für Ansprechpartner', 'Bestimmt, wie viele Partner nebeneinander angezeigt werden (Bootstrap Grid).'),
            'inputType' => 'select',
            'options' => array(
                'col-12' => '1 Spalte',
                'col-md-6' => '2 Spalten',
                'col-md-6 col-lg-4' => '3 Spalten (Standard)',
                'col-md-6 col-lg-3' => '4 Spalten',
                'col-auto' => 'Automatische Spaltenbreite',
            ),
            'default' => 'col-md-6 col-lg-4',
            'eval' => array('tl_class' => 'w50'),
        ),
        'row_justify_content' => array(
            'label' => array('Horizontale Ausrichtung der Partner-Reihe', 'Bestimmt, wie die Ansprechpartner-Boxen horizontal in der Reihe verteilt werden (justify-content).'),
            'inputType' => 'select',
            'options' => array(
                'justify-content-md-start' => 'Links',
                'justify-content-md-center' => 'Zentriert (Standard)',
                'justify-content-md-end' => 'Rechts',
                'justify-content-md-between' => 'Gleichmäßiger Abstand (zwischen)',
                'justify-content-md-around' => 'Gleichmäßiger Abstand (um)',
            ),
            'default' => 'justify-content-center',
            'eval' => array('tl_class' => 'w50'),
        ),

        // --- 3. Layout & Darstellung der einzelnen Partner-Boxen --- //
        'partner_layout' => array(
            'label' => array('Layout der Ansprechpartner-Boxen', 'Bestimmt die Anordnung von Bild und Text.'),
            'inputType' => 'select',
            'options' => array(
                'layout-image-left' => 'Bild links, Text rechts (Standard)',
                'layout-image-top' => 'Bild oben, Text unten',
            ),
            'default' => 'layout-image-left',
            'eval' => array('tl_class' => 'w50 clr'),
        ),
        'partner_alignment' => array(
            'label' => array('Textausrichtung der Partner-Boxen', 'Bestimmt die Textausrichtung innerhalb jeder einzelnen Ansprechpartner-Box.'),
            'inputType' => 'select',
            'options' => array(
                'text-start' => 'Linksbündig (Standard)',
                'text-center' => 'Zentriert',
                'text-end' => 'Rechtsbündig',
            ),
            'default' => 'text-start',
            'eval' => array('tl_class' => 'w50'),
        ),
        'email_icon_label' => array(
            'label' => array('Label für E-Mail-Text', ''),
            'inputType' => 'text',
            'default' => 'Nachricht schreiben',
            'eval' => array('tl_class' => 'w50'), // Neben 'Icons auf Bild'
        ),
        'phone_display_label' => array(
            'label' => array('Anzeigetext / Tooltip für Telefonnummer', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 '), // Nimmt jetzt eine ganze Zeile ein
        ),

        'hide_all_descriptions' => array(
            'label' => array('Beschreibungen hinter "Mehr erfahren" verstecken', 'Blendet alle Beschreibungen initial aus und zeigt einen "Mehr erfahren"-Link.'),
            'inputType' => 'checkbox',
            'default' => false,
            'eval' => array('tl_class' => 'w50 clr'), // clr für neue Zeile
        ),
        'more_info_label' => array(
            'label' => array('Text für "Mehr erfahren"', 'Text für den Link, der die Beschreibung ein-/ausblendet.'),
            'inputType' => 'text',
            'default' => 'Mehr erfahren',

            'eval' => array('tl_class' => 'w50'), // Neben Checkbox
        ),

        'title_below_name' => array(
            'label' => array('Titel unterhalb des Namens anzeigen', 'Zeigt den Titel/Abteilung unter dem Namen an (Standard: Titel: Name).'),
            'inputType' => 'checkbox',
            'default' => false,
            'eval' => array('tl_class' => ''), // Nimmt Platz neben Bildrundung ein
        ),
        'show_icons_on_image' => array(
            'label' => array('Kontakt-Icons auf Bild anzeigen', 'Stellt E-Mail und Telefon als Icons auf dem Bild dar.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' '), // clr für neue Zeile
            'default' => false,
        ),


        // --- 4. Funktionale Einstellungen --- //


        'round_images' => array(
            'label' => array('Bilder abrunden', 'Fügt die Klasse .rounded-circle zu den Bildern hinzu.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => ' '), // clr für neue Zeile
            'default' => false,
        ),
        'image_column_width' => array(
            'label' => array('Bildspaltenbreite (px)', 'Breite für die Bildspalte eingeben (nur bei Layout \"Bild links, Text rechts\").'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr', 'rgxp' => 'digit'),
            'dependsOn' => array(
                'field' => 'partner_layout',
                'value' => 'layout-image-left',
            ),
        ),


        // --- 5. Globale Bildgröße --- //
        'size' => array(
            'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'w50 clr'
            ),
        ),
        'add_filter_form' => array(
            'label' => array('Filter-Formular hinzufügen', 'Zeigt Filter-Buttons basierend auf den Tätigkeitsfeldern der Partner an.'),
            'inputType' => 'checkbox',
            'default' => false,
            'eval' => array('tl_class' => 'w50 clr'), // clr für neue Zeile
        ),
        'add_zip_filter' => array(
            'label' => array('PLZ-Filter integrieren', 'Ermöglicht Filterung nach Postleitzahl mit konfigurierbarem Umkreis.'),
            'inputType' => 'checkbox',
            'default' => false,
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'add_filter_form',
            ),
        ),
        'zip_filter_radius' => array(
            'label' => array('PLZ-Filter Umkreis (km)', 'Radius in Kilometern für die PLZ-Filterung.'),
            'inputType' => 'text',
            'default' => '150',
            'eval' => array('tl_class' => 'w50', 'rgxp' => 'digit', 'mandatory' => true),
            'dependsOn' => array(
                'field' => 'add_zip_filter',
            ),
        ),
        'zip_filter_blacklist' => array(
            'label' => array('PLZ-Filter Blacklist', 'Kommagetrennte Liste von Tags/Kategorien, bei denen der PLZ-Filter NICHT angezeigt wird (z.B. "Online Beratung, Telefonberatung").'),
            'inputType' => 'textarea',
            'eval' => array('tl_class' => 'w50', 'rows' => 3),
            'dependsOn' => array(
                'field' => 'add_zip_filter',
            ),
        ),
        // --- NEUE FELDER FÜR TEXT-FILTER ---
        'add_text_filter_option' => array(
            'label' => array('Filter-Option mit Text hinzufügen', 'Fügt eine weitere Filter-Schaltfläche hinzu, die einen Text anzeigt.'),
            'inputType' => 'checkbox',
            'default' => false,
            'eval' => array('tl_class' => 'w50 clr m12'), // m12 für etwas Abstand
            'dependsOn' => array(
                'field' => 'add_filter_form',
            ),
        ),
        'text_filter_button_label' => array(
            'label' => array('Button-Label für Text-Filter', 'Beschriftung der Schaltfläche für den Text-Filter.'),
            'inputType' => 'text',
            'default' => 'Weitere Informationen',
            'eval' => array('tl_class' => 'w50', 'mandatory' => true),
            'dependsOn' => array(
                'field' => 'add_text_filter_option',
            ),
        ),
        'text_filter_content' => array(
            'label' => array('Text für Text-Filter', 'Dieser Text wird angezeigt, wenn der Text-Filter aktiv ist.'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'add_text_filter_option',
            ),
        ),
        'add_general_inquiry' => array(
            'label' => array('Allgemeine Anfrage Button hinzufügen', 'Fügt einen Button für allgemeine Anfragen hinzu.'),
            'inputType' => 'checkbox',
            'default' => false,
            'eval' => array('tl_class' => 'w50 clr'),
            'dependsOn' => array(
                'field' => 'add_filter_form',
            ),
        ),
        'general_inquiry_button_label' => array(
            'label' => array('Button-Label für Allgemeine Anfrage', 'Beschriftung der Schaltfläche für allgemeine Anfragen.'),
            'inputType' => 'text',
            'default' => 'Allgemeine Anfrage',
            'eval' => array('tl_class' => 'w50', 'mandatory' => true),
            'dependsOn' => array(
                'field' => 'add_general_inquiry',
            ),
        ),
        'general_inquiry_content' => array(
            'label' => array('Text für Allgemeine Anfrage', 'Dieser Text wird angezeigt, wenn der Allgemeine Anfrage Button aktiv ist.'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'add_general_inquiry',
            ),
        ),
        // --- 6. Partner-Liste --- //
        'partners' => array(
            'label' => array('Ansprechpartner', ''),
            'elementLabel' => '%s. Ansprechpartner',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 6,
            'fields' => array(
                'title' => array(
                    'label' => array('Titel / Abteilung', 'z.B. "Vertrieb" oder "Technik"'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'mandatory' => false),
                ),
                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,webp',
                        'fieldType' => 'radio',
                        'tl_class' => 'clr'
                    ),
                ),
                'name' => array(
                    'label' => array('Name', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'email_link' => array(
                    'label' => array('E-Mail-Adresse oder Link', 'z.B. mailto:test@example.com oder /kontakt'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'rgxp' => 'url'), // URL Validierung hinzugefügt
                ),
                'phone' => array(
                    'label' => array('Telefonnummer', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'zip_code' => array(
                    'label' => array('Postleitzahl', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'linkedin_link' => array(
                    'label' => array('LinkedIn-Link', 'z.B. https://www.linkedin.com/in/benutzername'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'rgxp' => 'url'),
                ),
                // Beschreibung
                'description' => array(
                    'label' => array('Beschreibung', 'Detaillierte Informationen zum Ansprechpartner.'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
                // NEU: Tätigkeitsfelder/Tags
                'tags' => array(
                    'label' => array('Tätigkeitsfelder / Filter-Tags', 'Begriffe eingeben, nach denen gefiltert werden kann.'),
                    'inputType' => 'listWizard',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'buttons' => array(
                    'label' => array('Buttons', ''),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 5,
                    'eval' => array('tl_class' => 'clr'),
                    'fields' => ButtonHelper::getButtonConfig(),
                ),
                'animation_type' => array(
                    'label' => array(
                        'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
                    ),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => 'true', 'tl_class' => 'w50 clr')
                ),
            ),
        ),
    )
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);