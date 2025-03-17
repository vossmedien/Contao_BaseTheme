<?php
// rsce_product_payment_config.php

use Vsm\VsmHelperTools\Helper\ButtonHelper;
use Vsm\VsmHelperTools\Helper\GlobalElementConfig;

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
                'tax_rate' => array(
                    'label' => array('Steuersatz in %', 'Standard: 19% MwSt.'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50'),
                    'default' => 19
                ),
                'subscription_duration' => array(
                    'label' => array('Laufzeit in Monaten', 'Optional für zeitlich begrenzte Mitgliedschaften'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'natural', 'tl_class' => 'w50'),
                ),
                'is_subscription' => array(
                    'label' => array('Ist Abo-Produkt', 'Aktivieren, wenn es sich um ein Abonnement handelt'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50 clr'),
                ),
                'stripe_product_id' => array(
                    'label' => array('Stripe Produkt-ID', 'Die ID des bei Stripe erstellten Produkts'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50', 'mandatory' => true),
                    'dependsOn' => array(
                        'field' => 'is_subscription',
                        'value' => true,
                    ),
                ),
                'email_settings' => array(
                    'label' => array('E-Mail-Einstellungen', ''),
                    'inputType' => 'group',
                ),
                'sender_email' => array(
                    'label' => array('Absender-E-Mail', 'E-Mail-Adresse für den Absender'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'email', 'tl_class' => 'w50'),
                ),
                'admin_email' => array(
                    'label' => array('Admin-E-Mail', 'E-Mail-Adresse für Admin-Benachrichtigungen'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'email', 'tl_class' => 'w50'),
                ),
                'admin_template' => array(
                    'label' => array('Admin-E-Mail-Template', 'Template für die Admin-Benachrichtigung'),
                    'inputType' => 'select',
                    'options_callback' => function() {
                        // Einfache Verzeichnissuche ohne direkten Service-Zugriff
                        $templates = [];
                        $projectDir = \Contao\System::getContainer()->getParameter('kernel.project_dir');
                        
                        // Nach .html5 Templates in /templates/emails suchen
                        $templatesDir = $projectDir . '/templates/emails';
                        if (is_dir($templatesDir)) {
                            foreach (scandir($templatesDir) as $file) {
                                if (strpos($file, 'mail_') === 0 && substr($file, -6) === '.html5') {
                                    $name = substr($file, 0, -6);
                                    $templates[$name] = $name;
                                }
                            }
                        }
                        
                        // Nach .html.twig Templates in /templates/emails suchen
                        $twigTemplatesDir = $projectDir . '/templates/emails';
                        if (is_dir($twigTemplatesDir)) {
                            foreach (scandir($twigTemplatesDir) as $file) {
                                if (strpos($file, 'mail_') === 0 && substr($file, -10) === '.html.twig') {
                                    $name = substr($file, 0, -10);
                                    $templates[$name] = $name;
                                }
                            }
                        }
                        
                        return $templates;
                    },
                    'eval' => array('includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'),
                ),
                'user_template' => array(
                    'label' => array('Benutzer-E-Mail-Template', 'Template für die Benutzer-Benachrichtigung'),
                    'inputType' => 'select',
                    'options_callback' => function() {
                        // Einfache Verzeichnissuche ohne direkten Service-Zugriff
                        $templates = [];
                        $projectDir = \Contao\System::getContainer()->getParameter('kernel.project_dir');
                        
                        // Nach .html5 Templates in /templates/emails suchen
                        $templatesDir = $projectDir . '/templates/emails';
                        if (is_dir($templatesDir)) {
                            foreach (scandir($templatesDir) as $file) {
                                if (strpos($file, 'mail_') === 0 && substr($file, -6) === '.html5') {
                                    $name = substr($file, 0, -6);
                                    $templates[$name] = $name;
                                }
                            }
                        }
                        
                        // Nach .html.twig Templates in /templates/emails suchen
                        $twigTemplatesDir = $projectDir . '/templates/emails';
                        if (is_dir($twigTemplatesDir)) {
                            foreach (scandir($twigTemplatesDir) as $file) {
                                if (strpos($file, 'mail_') === 0 && substr($file, -10) === '.html.twig') {
                                    $name = substr($file, 0, -10);
                                    $templates[$name] = $name;
                                }
                            }
                        }
                        
                        return $templates;
                    },
                    'eval' => array('includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'),
                ),
                'file_settings' => array(
                    'label' => array('Datei-Einstellungen', ''),
                    'inputType' => 'group',
                ),
                'file_sale' => array(
                    'label' => array('Datei-Verkauf', 'Aktiviert den Verkauf einer digitalen Datei (Download)'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr'),
                ),
                'download_file' => array(
                    'label' => array('Download-Datei', ''),
                    'inputType' => 'fileTree',
                    'eval' => array(
                        'multiple' => false,
                        'fieldType' => 'radio',
                        'filesOnly' => true,
                        'mandatory' => true,
                        'tl_class' => 'clr' 
                    ),
                    'dependsOn' => array(
                        'field' => 'file_sale',
                    ),
                ),
                'download_expires' => array(
                    'label' => array('Ablauf in Tagen', 'Nach wie vielen Tagen läuft der Download ab?'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'natural', 'tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'file_sale',
                    ),
                ),
                'download_limit' => array(
                    'label' => array('Download-Limit', 'Maximale Anzahl an Downloads'),
                    'inputType' => 'text',
                    'eval' => array('rgxp' => 'natural', 'tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'file_sale',
                    ),
                ),
                'button_settings' => array(
                    'label' => array('Button-Einstellungen', ''),
                    'inputType' => 'group',
                ),
                'button_text' => array(
                    'label' => array('Button-Text', 'Standard: "Jetzt kaufen"'),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                ),
                'disable_payment' => array(
                    'label' => array('Bezahlung deaktivieren', 'Versteckt den Bezahl-Button für dieses Produkt'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'w50'),
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
                'status_settings' => array(
                    'label' => array('Status-Einstellungen', ''),
                    'inputType' => 'group',
                ),
                'status' => array(
                    'label' => array('Status', ''),
                    'inputType' => 'select',
                    'options' => array(
                        'active' => 'Aktiv',
                        'sold_out' => 'Ausverkauft',
                        'hidden' => 'Versteckt'
                    ),
                    'eval' => array('tl_class' => 'w50'),
                ),
                'sold_out_text' => array(
                    'label' => array('Ausverkauft-Text', ''),
                    'inputType' => 'text',
                    'eval' => array('tl_class' => 'w50'),
                    'dependsOn' => array(
                        'field' => 'status',
                        'value' => 'sold_out',
                    ),
                ),
                'stripe_invoice' => array(
                    'label' => array('Stripe-Rechnung', 'Einstellungen für die Rechnungsstellung'),
                    'inputType' => 'group',
                ),
                'create_invoice' => array(
                    'label' => array('Stripe-Rechnung aktivieren', 'Erstellt automatisch Rechnungen und sendet diese an Kunden'),
                    'inputType' => 'checkbox',
                    'eval' => array('tl_class' => 'clr', 'isBoolean' => true),
                ),
                'invoice_note' => array(
                    'label' => array('Hinweis', 'Informationen zur Rechnungsstellung'),
                    'inputType' => 'explanation',
                    'eval' => array('text' => 'Stripe erstellt automatisch Rechnungen für jede Zahlung und sendet diese per E-Mail an den Kunden. Hierfür werden die Kundendaten aus dem Zahlungsformular verwendet.', 'tl_class' => 'clr'),
                ),
            ),
        ),
    ),
);