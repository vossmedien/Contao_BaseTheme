<?php


use Vsm\VsmAbTest\Helper\RockSolidConfigHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;
use Vsm\VsmHelperTools\Helper\ButtonHelper;

$config = array(
    'label' => array('Custom | Leistungen im Überblick (services_overview)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'standardFields' => array('headline', 'cssID'),
    'moduleCategory' => 'miscellaneous',
        'wrapper' => array(
        'type' => 'none',
    ),
    'fields' => array(
        'topline' => array(
            'label' => array('Topline', 'Text oberhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'subline' => array(
            'label' => array('Subline', 'Text unterhalb der Überschrift'),
            'inputType' => 'text',
            'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
        ),
        'animation_type' => array(
            'label' => array('Animation der Liste', 'Art der Einblendeanimation für die Listenelemente (links). Siehe https://animate.style/'),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => true, 'tl_class' => 'w50')
        ),
        'disable_hover' => array(
            'label' => array('Interaktion per Klick', 'Wenn aktiviert, wird der Inhalt rechts nur bei Klick auf ein Listenelement gewechselt (statt bei Mouseover).'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 m12'),
        ),
        'show_arrow' => array(
            'label' => array('Pfeil anzeigen', 'Blendet den Pfeil (->) vor den Titeln in der linken Liste ein.'),
            'inputType' => 'checkbox',
            'default' => true, // Standardmäßig Pfeil anzeigen
            'eval' => array('tl_class' => 'w50 m12'),
        ),
        'size' => array(
            'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
            'inputType' => 'imageSize',
            'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => array(
                'rgxp' => 'digit',
                'includeBlankOption' => true,
                'tl_class' => 'clr'
            ),
        ),
        'services' => array(
            'label' => array('Leistungen', 'Definieren Sie hier die einzelnen Leistungen, die angezeigt werden sollen.'),
            'elementLabel' => '%s. Leistung',
            'inputType' => 'list',
            'minItems' => 1,
            'maxItems' => 20,
            'fields' => array(
                'title' => array(
                    'label' => array('Titel', 'Angezeigter Titel in der linken Liste.'),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50 clr'),
                ),
                'link' => array(
                    'label' => array('Link', 'Optionaler Link für das Listenelement.'),
                    'inputType' => 'url',
                    'eval' => array('mandatory' => false, 'tl_class' => 'w50'),
                ),
                'content_headline' => array(
                    'label' => array('Überschrift', 'Überschrift im rechten Inhaltsbereich, wenn dieses Element aktiv ist.'),
                    'inputType' => 'text',
                     'eval' => array('tl_class' => 'w50 clr'),
                ),
                 'content_hl' => array(
                     'label' => array('Typ der Überschrift', ''),
                     'inputType' => 'select',
                     'options' => GlobalElementConfig::getHeadlineTagOptions(),
                     'eval' => array('includeBlankOption' => true, 'tl_class' => 'w50'),
                 ),
                'content_topline' => array(
                    'label' => array('Topline', 'Text oberhalb der Überschrift im rechten Inhaltsbereich.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50 clr', 'allowHtml' => true),
                ),
                'content_subline' => array(
                    'label' => array('Subline', 'Text unterhalb der Überschrift im rechten Inhaltsbereich.'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'allowHtml' => true),
                ),
                 'headline_animation_type' => array(
                     'label' => array('Animation der Überschrift', 'Animation für Topline, Überschrift und Subline.'),
                     'inputType' => 'select',
                     'options' => GlobalElementConfig::getAnimations(),
                     'eval' => array('chosen' => true, 'tl_class' => 'w50 clr')
                 ),
                'image' => array(
                    'label' => array('Bild', 'Optionales Bild im rechten Inhaltsbereich.'),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'filesOnly' => true,
                        'extensions' => Contao\Config::get('validImageTypes'),
                        'fieldType' => 'radio',
                        'mandatory' => false,
                        'tl_class' => 'w50',
                    ),
                ),
                'image_no_lazy' => array(
                    'label' => array('Bild ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Bild'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'image_animation_type' => array(
                    'label' => array('Animation des Bildes', ''),
                    'inputType' => 'select',
                    'options' => GlobalElementConfig::getAnimations(),
                    'eval' => array('chosen' => true, 'tl_class' => 'w50 clr')
                ),
                'content_text' => array(
                    'label' => array('Text', 'Optionaler Text im rechten Inhaltsbereich (unterhalb des Bildes).'),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
                 'text_animation_type' => array(
                     'label' => array('Animation des Textes', ''),
                     'inputType' => 'select',
                     'options' => GlobalElementConfig::getAnimations(),
                     'eval' => array('chosen' => true, 'tl_class' => 'w50 clr')
                 ),
                'buttons' => array(
                    'label' => array('Buttons', 'Optionale Buttons im rechten Inhaltsbereich (unterhalb des Textes).'),
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 0,
                    'maxItems' => 20,
                    'eval' => array('tl_class' => 'clr'),
                    'fields' => ButtonHelper::getButtonConfig(),
                ),
                'icon' => array(
                    'label' => array('Icon/Bild', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'extensions' => Contao\Config::get('validImageTypes'),
                        'tl_class' => 'w50',
                    ),
                ),
                'icon_no_lazy' => array(
                    'label' => array('Icon/Bild ohne Lazy-Loading laden', 'Deaktiviert das Lazy-Loading für das Icon/Bild'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),
            ),
            'subpalettes' => array(
                 'image' => 'size,open_lightbox', // Zeige diese Felder nur wenn 'image' ausgewählt ist
            ),
            'fields_for_subpalettes' => array(
                 'size' => array(
                    'label' => array('Bildgröße', 'Hier können Sie die Abmessungen des Bildes und den Skalierungsmodus festlegen.'),
                    'inputType' => 'imageSize',
                    'options' => Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                         'tl_class' => 'w50'
                    ),
                ),
                'open_lightbox' => array(
                    'label' => array('Bild in Lightbox öffnen', ''),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50 m12'),
                ),
            )
        ),
    ),
);

// A/B Test Felder hinzufügen
return RockSolidConfigHelper::addAbTestFields($config);
return RockSolidConfigHelper::addAbTestFields($config);