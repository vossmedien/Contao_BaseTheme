<?php

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */

// DCA-Konfiguration für die Stripe-Zahlungssitzungen

$GLOBALS['TL_DCA']['tl_stripe_payment_sessions'] = [
    // Konfiguration
    'config' => [
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'session_id' => 'unique',
                'download_token' => 'index'
            ]
        ],
    ],

    // Liste der Felder
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'created_at' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'session_id' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'customer_data' => [
            'sql' => "text NULL"
        ],
        'product_data' => [
            'sql' => "text NULL"
        ],
        'payment_data' => [
            'sql' => "text NULL"
        ],
        'status' => [
            'sql' => "varchar(64) NOT NULL default 'pending'"
        ],
        'paid_at' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'amount' => [
            'sql' => "decimal(10,2) NOT NULL default '0.00'"
        ],
        'currency' => [
            'sql' => "varchar(3) NOT NULL default 'EUR'"
        ],
        'user_id' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'download_token' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'download_url' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'download_file' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'download_expires' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'download_limit' => [
            'sql' => "int(10) unsigned NOT NULL default '3'"
        ],
        'download_count' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'last_download' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'emails_sent' => [
            'sql' => "char(1) NOT NULL default ''"
        ],
        'stripe_customer_id' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'stripe_session_id' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'stripe_payment_intent_id' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'metadata' => [
            'sql' => "text NULL"
        ]
    ]
]; 