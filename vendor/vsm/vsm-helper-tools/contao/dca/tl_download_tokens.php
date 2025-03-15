<?php

$GLOBALS['TL_DCA']['tl_download_tokens'] = [
    'config' => [
        'dataContainer' => 'Table',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'token' => 'index'
            ]
        ]
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'token' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'file_id' => [
            'sql' => "binary(16) NULL"
        ],
        'expires' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'download_limit' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'download_count' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'order_id' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'customer_email' => [
            'sql' => "varchar(255) NOT NULL default ''"
        ]
    ]
]; 