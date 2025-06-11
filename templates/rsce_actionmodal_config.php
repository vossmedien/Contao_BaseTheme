<?php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

// rsce_my_element_config.php
return array(
    'label' => array('Custom | Modales Fenster für Aktionen (actionmodal)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('cssID'),
    'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'settings_headline' => array(
            'label' => array('Überschrift des modalen Fensters', ''),
            'inputType' => 'group',
        ),


        'modal_topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),

        'close_button_color' => array(
            'label' => array('Farbe der Schließen-Buttons (X-Icons)', 'In HEX oder RGB angeben. Standard: Schwarz oder Weiß, je nach Hintergrund.'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),

        'modal_subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'modal_headline' => array(
            'label' => array('Überschrift für Modal-Header', 'Wenn leer, wird der Header ausgeblendet'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'modal_headline_type' => array(
            'label' => array(
                'de' => array('Typ der Überschrift', ''),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'eval' => array('tl_class' => 'w50'),
        ),

        'modal_onlystyle' => array(
            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'settings_modal' => array(
            'label' => array('Einstellungen für modales Fenster', ''),
            'inputType' => 'group',
        ),


        'modal_size' => array(
            'label' => array(
                'de' => array('Größe des modalen Fensters', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                '' => 'Standard',
                'modal-sm' => 'Klein',
                'modal-lg' => 'Groß',
                'modal-xl' => 'Sehr groß',
                'modal-fullscreen' => 'Vollbild',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),

        'modal_backgroundcolor' => array(
            'label' => array('Hintergrundfarbe für das gesamte Modal', 'in HEX oder RGB angeben, Standard: Weiß'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'modal_text_color' => array(
            'label' => array('Schriftfarbe für den Modal-Inhalt', 'in HEX oder RGB angeben. Standard: Wird vom CSS geerbt.'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50 clr'),
        ),


        'opening_settings_group' => array(
            'label' => array('Verhalten beim Öffnen', ''),
            'inputType' => 'group',
        ),

        'opening_type' => array(
            'label' => array(
                'de' => array('Art und Weise wie das Modal geöffnet werden soll', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                '' => 'Direkt bei Seitenaufruf öffnen',
                'initial_hidden' => 'Nicht automatisch öffnen (manuelles Triggern nötig)',
                'after_scrolling' => 'Nach einer gewissen Scroll-Distanz einblenden',
                'after_time' => 'Nach einer Weile automatisch einblenden',
                'exit_intent' => 'Beim Verlassen des Browserfensters (Exit Intent)',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),


        'show_delay' => array(
            'label' => array('Zeit in Sekunden bis zum Einblenden', 'Standard: 15'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'opening_type',
                'value' => 'after_time',
            ),
            'eval' => array('tl_class' => 'w50'),

        ),

        'scroll_amount' => array(
            'label' => array('"Scrollweg" in Pixel bis zum Einblenden', 'Standard: 700'),
            'inputType' => 'text',
            'dependsOn' => array(
                'field' => 'opening_type',
                'value' => 'after_scrolling',
            ),
            'eval' => array('tl_class' => 'w50'),
        ),

        'only_mobile' => array(
            'label' => array('Nur auf mobile anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),


        'cookie_hide' => array(
            'label' => array('Verstecken nach gesetztem Cookie', 'Nachdem das Fenster geschlossen wurde, wird ein Cookie gesetzt und das modale Fenster wird nicht erneut angezeigt.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'animation_settings_group' => array(
            'label' => array('Animationen', ''),
            'inputType' => 'group',
        ),

        'animation_type_element' => array(
            'label' => array('Animation: Gesamtes Modal', 'Globale Animation für das äußere Modal-Fenster.'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('tl_class' => 'w50', 'includeBlankOption' => true),
        ),

        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation (Modal-Dialog)', 'Siehe https://animate.style/ für Beispiele. Wird ignoriert, wenn "Animation: Gesamtes Modal" gesetzt ist.'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'default' => 'animate__fadeInUp',
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'animation_type_element',
                'value' => array('', null),
            ),
        ),


        'settings_footer' => array(
            'label' => array('Fußbereich Einstellungen', ''),
            'inputType' => 'group',
        ),

        'show_footer_close' => array(
            'label' => array('Schließen Button zum Footer hinzufügen', 'Funktioniert nicht MIT Sponsoren-Logos!'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr', 'submitOnChange' => true),
        ),

        'footer_close_button_type' => array(
            'label' => array('Button-Stil (Schließen-Button Footer)', ''),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getButtonTypes(),
            'default' => 'btn-secondary',
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'show_footer_close',
            ),
        ),

        'footer_close_button_size' => array(
            'label' => array('Button-Größe (Schließen-Button Footer)', ''),
            'inputType' => 'select',
            'options' => array(
                '' => 'Standard',
                'btn-sm' => 'Klein (btn-sm)',
                'btn-lg' => 'Groß (btn-lg)',
            ),
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'show_footer_close',
            ),
        ),

        'remove_image_padding' => array(
            'label' => array('Außenabstand des Bildes entfernen', 'das Bild liegt dann an der Kante'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'settings_image' => array(
            'label' => array('Kopfbereich (Bild/Video & optionale rechte Spalte)', ''),
            'inputType' => 'group',
        ),

        'image' => array(
            'label' => array('Bild/Video für Kopfbereich', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,webm,webp,svg',
                'tl_class' => 'w50 clr',
                'submitOnChange' => true
            ),
        ),

        'add_image_right_column_content' => array(
            'label' => array('Rechte Spalte im Kopfbereich hinzufügen', 'Ermöglicht Text und Hintergrundfarbe rechts neben dem Bild/Video.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 clr', 'submitOnChange' => true),
            'dependsOn' => array(
                'field' => 'image',
            ),
        ),

        'image_headline_left' => array(
            'label' => array('Überschrift für die linke Spalte des Bildes', '(ca. 35% breit)'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'image',
            ),
        ),

        'image_headline_right' => array(
            'label' => array('Überschrift für Textbereich auf der rechten Seite', ''),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'add_image_right_column_content',
            ),
        ),

        'image_text_right' => array(
            'label' => array('Langtext für Textbereich auf der rechten Seite', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'add_image_right_column_content',
            ),
        ),

        'image_right_col_background_color' => array(
            'label' => array('Hintergrundfarbe für rechte Spalte', 'in HEX oder RGB angeben, Standard: Schwarz mit .75 Deckungskraft'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'add_image_right_column_content',
            ),
        ),

        'image_right_col_text_color' => array(
            'label' => array('Textfarbe für rechte Spalte', 'in HEX oder RGB angeben, Standard: weiß'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'add_image_right_column_content',
            ),
        ),

        'image_main' => array(
            'label' => array('Bild/Video', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,svg,webp',
                'tl_class' => 'w50'
            ),
        ),

        'as_bg' => array(
            'label' => array('als Hintergrund', 'dadurch bekommt der Bereich eine feste Höhe und das Bild/Video wird evtl. beschnitten'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'fixed_height' => array(
            'label' => array('Feste Höhe', 'Bild-Bereich eine feste Höhe inkl. Einheit (z. B. px) zuweisen'),
            'inputType' => 'text',
            'eval' => array(
                 'tl_class' => 'w50'
            ),
        ),


        'settings_progressbar' => array(
            'label' => array('Fortschrittsanzeige', ''),
            'inputType' => 'group',
        ),
        'progress_image' => array(
            'label' => array('Bild für Fortschrittsanzeige', 'Alternative'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4,webm,ogv,svg,webp',
            ),
        ),

        'progress_amount' => array(
            'label' => array('Fortschrittsanzeige', 'Angeben, wie oft das Bild der Fortschrittsanzeige wiederholt werden soll oder zu wie viel % die Anzeige fortgeschritten sein soll, wenn kein Bild ausgewählt ist'),
            'inputType' => 'text',
        ),


        'settings_text' => array(
            'label' => array('Unterer Bereich', ''),
            'inputType' => 'group',
        ),


        'image_left' => array(
            'label' => array('Bild für unteren Bereich', 'ca. 35% breit'),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,svg,webp',
            ),
        ),


        'topline' => array(
            'label' => array('Topline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),

        'subline' => array(
            'label' => array('Subline', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'headline' => array(
            'label' => array('Standard-Überschrift', ''),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50'),
        ),

        'headline_type' => array(
            'label' => array(
                'de' => array('Typ der Überschrift', ''),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getHeadlineTagOptions(),
            'eval' => array('tl_class' => 'w50'),
        ),

        'onlystyle' => array(
            'label' => array('Text nur als Überschrift darstellen (hat dementsprechend keinen Einfluss auf SEO)', 'macht Sinn wenn man z. B. eine H3 unterhalb einer H1 anzeigen möchte, ohne dass eine H2 existiert'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'text' => array(
            'label' => array('Langtext', 'es können auch Inserttags verwendet werden um Nodes, Artikel oder andere Elemente zu inkludieren'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),


        'buttons_group' => array(
            'label' => array('Buttons & Sponsoren', ''),
            'inputType' => 'group',
        ),

        'buttons' => array(
            'label' => array('Buttons', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20,
            'eval' => array('tl_class' => 'clr'),
            'fields' => ButtonHelper::getButtonConfig(),
        ),


        'sponsors' => array(
            'label' => array('Logos (z. B. Sponsoren)', 'rechts unten, blendet ggf . den schließen - Button aus'),
            'elementLabel' => ' % s . Logo',
            'inputType' => 'list',
            'minItems' => 0,
            'maxItems' => 20,
            'fields' => array(
                'image' => array(
                    'label' => array('Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => 'jpg,jpeg,png,svg,webp',
                    ),
                ),
                'link' => array(
                    'label' => array('Verlinkung', 'optional'),
                    'inputType' => 'url',
                ),
            ),
        ),
    ),
);
