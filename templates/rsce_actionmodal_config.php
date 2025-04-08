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
            'options' => array(
                'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                'h4' => 'H4',
                'h5' => 'H5',
                'h6' => 'H6',
            ),
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

        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
        ),

        'modal_backgroundcolor' => array(
            'label' => array('Hintergrundfarbe für das gesamte Modal', 'in HEX oder RGB angeben, Standard: Weiß'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'clr'),
        ),


        'opening_type' => array(
            'label' => array(
                'de' => array('Art und Weise wie das Modal geöffnet werden soll', ''),
            ),
            'inputType' => 'select',
            'options' => array(
                '' => 'Direkt bei Seitenaufruf öffnen',
                'initial_hidden' => 'Nicht automatisch öffnen, bleibt bis zum manuellen "öffnen" versteckt',
                'after_scrolling' => 'Nach einer gewissen Scrollzeit einblenden',
                'after_time' => 'Nach einer Weile automatisch einblenden',
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
            'eval' => array('tl_class' => 'clr'),
        ),


        'cookie_hide' => array(
            'label' => array('Verstecken nach gesetztem Cookie', 'Nachdem das Fenster geschlossen wurde, wird ein Cookie gesetzt und das modale Fenster wird nicht erneut angezeigt.'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),


        'show_footer_close' => array(
            'label' => array('Schließen Button zum Footer hinzufügen', 'Funktioniert nicht MIT Sponsoren-Logos!'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),
        'remove_image_padding' => array(
            'label' => array('Außenabstand des Bildes entfernen', 'das Bild liegt dann an der Kante'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),

        'settings_image' => array(
            'label' => array('Kopfbereich', ''),
            'inputType' => 'group',
        ),

        'image_headline_left' => array(
            'label' => array('Überschrift für die linke Spalte des Bildes', '(ca. 35% breit)'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
        ),

        'image_headline_right' => array(
            'label' => array('Überschrift für Textbereich auf der rechten Seite', ''),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
        ),

        'image_text_right' => array(
            'label' => array('Langtext für Textbereich auf der rechten Seite', ''),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),

        'image_right_col_background_color' => array(
            'label' => array('Hintergrundfarbe für rechte Spalte', 'in HEX oder RGB angeben, Standard: Schwarz mit .75 Deckungskraft'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
        ),

        'image_right_col_text_color' => array(
            'label' => array('Textfarbe für rechte Spalte', 'in HEX oder RGB angeben, Standard: weiß'),
            'inputType' => 'text',
            'eval' => array('allowHtml' => true, 'tl_class' => 'w50'),
        ),

        'image' => array(
            'label' => array('Bild/Video', ''),
            'inputType' => 'fileTree',
            'eval' => array(
                'multiple' => false,
                'fieldType' => 'radio',
                'filesOnly' => true,
                'extensions' => 'jpg,jpeg,png,mp4',
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
            'dependsOn' => array(
                'field' => 'as_bg',
            ),
            'eval' => array(
                'mandatory' => true,
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
                'extensions' => 'jpg,jpeg,png,mp4,webm,ogv',
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
                'extensions' => 'jpg,jpeg,png',
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
            'options' => array(
                'h1' => 'H1 (Haupt-Headline für SEO, darf nur 1x vorkommen)',
                'h2' => 'H2 (Sollte H1 thematisch untergeordnet sein)',
                'h3' => 'H3 (Sollte H2 thematisch untergeordnet sein)',
                'h4' => 'H4',
                'h5' => 'H5',
                'h6' => 'H6',
            ),
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


        'buttons' => array(
            'label' => array('Buttons', ''),
            'elementLabel' => '%s. Button',
            'inputType' => 'list',
            'minItems' => 1,
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
