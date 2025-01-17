<?php
// rsce_product_payment_config.php

use VSM_HelperFunctions\ButtonHelper;
use VSM_HelperFunctions\GlobalElementConfig;

return array(
    'label' => array('Custom | Produkt-Payment (Grid)', ''),
    'types' => array('content'),
    'contentCategory' => 'Custom',
    'moduleCategory' => 'miscellaneous',
    'standardFields' => array('headline', 'cssID'),
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
        'description' => array(
            'label' => array('Beschreibung', 'Optionaler Text unter der Überschrift'),
            'inputType' => 'textarea',
            'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
        ),
        'animation_type' => array(
            'label' => array(
                'de' => array('Art der Einblendeanimation', 'Siehe https://animate.style/ für Beispiele'),
            ),
            'inputType' => 'select',
            'options' => GlobalElementConfig::getAnimations(),
            'eval' => array('chosen' => 'true', 'tl_class' => 'w50')
        ),

        'stripe_settings' => array(
            'label' => array('Stripe Einstellungen', ''),
            'inputType' => 'group',
        ),
        'stripe_enabled' => array(
            'label' => array('Stripe Zahlungen aktivieren', 'Global für alle Produkte'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),
        'stripe_public_key' => array(
            'label' => array('Stripe Public Key', ''),
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'stripe_enabled',
            ),
        ),
        'stripe_currency' => array(
            'label' => array('Währung', ''),
            'inputType' => 'select',
            'options' => array(
                'eur' => 'EUR',
                'usd' => 'USD',
                'gbp' => 'GBP'
            ),
            'eval' => array('tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'stripe_enabled',
            ),
        ),
        'success_page' => array(
            'label' => array('Erfolgsseite', 'Weiterleitung nach erfolgreicher Zahlung'),
            'inputType' => 'pageTree',
            'eval' => array('fieldType' => 'radio', 'tl_class' => 'clr'),
            'dependsOn' => array(
                'field' => 'stripe_enabled',
            ),
        ),

        'form_settings' => array(
            'label' => array('Formular-Einstellungen', ''),
            'inputType' => 'group',
        ),
        'personal_data_fields' => array(
            'label' => array('Formularfelder', 'Felder die im Zahlungsformular angezeigt werden'),
            'inputType' => 'checkboxWizard',
            'options' => array(
                'salutation' => 'Anrede',
                'firstname' => 'Vorname',
                'lastname' => 'Nachname',
                'email' => 'E-Mail-Adresse',
                'phone' => 'Telefonnummer',
                'phone_required' => 'Telefonnummer (Pflichtfeld)',
                'street' => 'Straße',
                'postal' => 'PLZ',
                'city' => 'Ort',
                'country' => 'Land',
                'company' => 'Firma',
                'birthday' => 'Geburtsdatum'
            ),
            'eval' => array('multiple' => true, 'tl_class' => 'clr')
        ),
        'show_privacy_notice' => array(
            'label' => array('Datenschutzhinweis anzeigen', ''),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50'),
        ),
        'privacy_page' => array(
            'label' => array('Datenschutz-Seite', ''),
            'inputType' => 'pageTree',
            'eval' => array('fieldType' => 'radio', 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'show_privacy_notice',
            ),
        ),

        'user_settings' => array(
            'label' => array('Benutzer-Einstellungen', ''),
            'inputType' => 'group',
        ),
        'create_user' => array(
            'label' => array('Benutzer anlegen', 'Erstellt automatisch einen Contao-Mitglied-Account'),
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'clr'),
        ),
        'member_group' => array(
            'label' => array('Mitgliedergruppe', 'Standardgruppe für neue Benutzer'),
            'inputType' => 'select',
            'options_callback' => function () {
                $groups = [];
                $result = \Contao\Database::getInstance()
                    ->prepare("SELECT id, name FROM tl_member_group ORDER BY name")
                    ->execute();

                while ($result->next()) {
                    $groups[$result->id] = $result->name;
                }

                return $groups;
            },
            'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
            'dependsOn' => array(
                'field' => 'create_user',
            ),
        ),

        'grid_settings' => array(
            'label' => array('Grid-Einstellungen', ''),
            'inputType' => 'group',
        ),
        'grid_columns' => array(
            'label' => array('Spalten Desktop', 'Standard: 3'),
            'inputType' => 'select',
            'options' => array(
                '2' => '2 Spalten',
                '3' => '3 Spalten',
                '4' => '4 Spalten'
            ),
            'eval' => array('tl_class' => 'w50')
        ),
        'grid_columns_tablet' => array(
            'label' => array('Spalten Tablet', 'Standard: 2'),
            'inputType' => 'select',
            'options' => array(
                '1' => '1 Spalte',
                '2' => '2 Spalten',
                '3' => '3 Spalten'
            ),
            'eval' => array('tl_class' => 'w50')
        ),

        'products' => array(
            'label' => array('Produkte', ''),
            'elementLabel' => '%s. Produkt',
            'inputType' => 'list',
            'minItems' => 1,
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
                        'tl_class' => 'clr'
                    ),
                ),
                'image_size' => array(
                    'label' => array('Bildgröße', ''),
                    'inputType' => 'imageSize',
                    'options' => \Contao\System::getContainer()->get('contao.image.sizes')->getAllOptions(),
                    'reference' => &$GLOBALS['TL_LANG']['MSC'],
                    'eval' => array(
                        'rgxp' => 'digit',
                        'includeBlankOption' => true,
                    ),
                ),
                'label' => array(
                    'label' => array('Label', 'Optionaler Text über dem Titel'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'title' => array(
                    'label' => array('Titel', ''),
                    'inputType' => 'text',
                    'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
                ),
                'subtitle' => array(
                    'label' => array('Untertitel', 'Optionaler Text unter dem Titel'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'description' => array(
                    'label' => array('Beschreibung', ''),
                    'inputType' => 'textarea',
                    'eval' => array('rte' => 'tinyMCE', 'tl_class' => 'clr'),
                ),
                'features' => array(
                    'label' => array('Features', 'Ein Feature pro Zeile'),
                    'inputType' => 'textarea',
                    'eval' => array('style' => 'height:60px', 'tl_class' => 'clr'),
                ),
                'price' => array(
                    'label' => array('Preis', 'z.B. 99.00'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50'),
                ),
                'old_price' => array(
                    'label' => array('Alter Preis', 'Optional für Streichpreis'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50'),
                ),
                'price_info' => array(
                    'label' => array('Preis-Info', 'Optionaler Text unter dem Preis'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'subscription_duration' => array(
                    'label' => array('Laufzeit in Monaten', 'Optional für zeitlich begrenzte Mitgliedschaften'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'natural', 'tl_class' => 'w50'),
                ),
                'plunk_event_name' => array(
                    'label' => array('Event Name', 'Für Plunk Automation'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'status' => array(
                    'label' => array('Status', ''),
                    'inputType' => 'radio',
                    'options' => array(
                        'active' => 'Aktiv (kaufbar)',
                        'sold_out' => 'Ausverkauft',
                        'hidden' => 'Versteckt'
                    ),
                    'default' => 'active',
                    'eval' => array('mandatory' => true, 'tl_class' => 'clr')
                ),
                'sold_out_text' => array(
                    'label' => array('Ausverkauft-Text', 'Wird angezeigt wenn Status "Ausverkauft"'),
                    'inputType' => 'text',
                    'dependsOn' => array(
                        'field' => 'status',
                        'value' => 'sold_out',
                    ),
                ),
                'disable_payment' => array(
                    'label' => array('Zahlung deaktivieren', 'Überschreibt die globale Stripe-Einstellung'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'buttons' => [
                    'label' => ['Buttons', ''],
                    'elementLabel' => '%s. Button',
                    'inputType' => 'list',
                    'minItems' => 1,
                    'maxItems' => 20,
                    'eval' => ['tl_class' => 'clr'],
                    'fields' => ButtonHelper::getButtonConfig(),
                ],
            ),
        ),
    ),
);