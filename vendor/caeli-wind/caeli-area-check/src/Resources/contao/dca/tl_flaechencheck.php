<?php

use Contao\DC_Table;

// tl_flaechencheck dca
$GLOBALS['TL_DCA']['tl_flaechencheck'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['tstamp DESC'],
            'panelLayout' => 'filter;search,limit',
            'flag' => 1,
            'label' => ['name', 'vorname', 'searched_address']
        ],
        'label' => [
            'fields' => ['name', 'vorname', 'searched_address'],
            'format' => '%s %s (%s)'
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            ]
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_flaechencheck']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_flaechencheck']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? 'Wollen Sie diesen Eintrag wirklich löschen?') . '\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_flaechencheck']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ]
    ],
    'palettes' => [
        '__selector__' => [],
        'default' => 'name,vorname,phone,email,searched_address,geometry,park_id,park_rating'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'name' => [
            'label' => ['Nachname', ''],
            'inputType' => 'text',
            'eval' => ['mandatory'=>true, 'maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'vorname' => [
            'label' => ['Vorname', ''],
            'inputType' => 'text',
            'eval' => ['mandatory'=>true, 'maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'phone' => [
            'label' => ['Telefon', ''],
            'inputType' => 'text',
            'eval' => ['maxlength'=>64],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'email' => [
            'label' => ['E-Mail', ''],
            'inputType' => 'text',
            'eval' => ['rgxp'=>'email', 'maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'searched_address' => [
            'label' => ['Gesuchte Adresse', ''],
            'inputType' => 'text',
            'eval' => ['maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'geometry' => [
            'label' => ['Geometrie', 'GeoJSON oder Koordinaten'],
            'inputType' => 'textarea',
            'eval' => ['maxlength'=>2048, 'allowHtml'=>true, 'decodeEntities'=>true, 'style'=>'height:80px'],
            'sql' => "text NULL"
        ],
        'park_id' => [
            'label' => ['Park-ID', ''],
            'inputType' => 'text',
            'eval' => ['maxlength'=>64],
            'sql' => "varchar(64) NOT NULL default ''"
        ],
        'park_rating' => [
            'label' => ['Park-Bewertung', ''],
            'inputType' => 'textarea',
            'eval' => ['maxlength'=>2048, 'allowHtml'=>true, 'decodeEntities'=>true, 'style'=>'height:80px'],
            'sql' => "text NULL"
        ]
    ]
]; 