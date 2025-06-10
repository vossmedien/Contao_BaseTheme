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
            'flag' => 6, // Nach Datum gruppieren
        ],
        'label' => [
            'fields' => ['tstamp', 'name', 'vorname', 'searched_address'],
            'label_callback' => ['tl_flaechencheck_callbacks', 'formatLabel']
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
        'default' => 'name,vorname,phone,email,searched_address,geometry,park_id,park_rating,status,error_message,uuid'
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'label' => ['Zeitstempel', ''],
            'flag' => 6,
            'sorting' => true,
            'eval' => ['rgxp' => 'datim'],
            'sql' => "int(10) unsigned NOT NULL default 0"
        ],
        'name' => [
            'label' => ['Nachname', ''],
            'inputType' => 'text',
            'sorting' => true,
            'eval' => ['mandatory'=>true, 'maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'vorname' => [
            'label' => ['Vorname', ''],
            'inputType' => 'text',
            'sorting' => true,
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
            'sorting' => true,
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
        ],
        'status' => [
            'label' => ['Status', ''],
            'inputType' => 'select',
            'options' => ['success' => 'Erfolgreich', 'failed' => 'Fehlgeschlagen', 'failed_with_rating' => 'Fehlgeschlagen mit Bewertung'],
            'sorting' => true,
            'filter' => true,
            'eval' => ['maxlength'=>32],
            'sql' => "varchar(32) NOT NULL default 'success'"
        ],
        'error_message' => [
            'label' => ['Fehlermeldung', ''],
            'inputType' => 'text',
            'eval' => ['maxlength'=>255],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'uuid' => [
            'label' => ['Eindeutige ID', 'Zufällige UUID für sichere URL-Parameter'],
            'inputType' => 'text',
            'eval' => ['maxlength'=>36, 'unique'=>true, 'doNotCopy'=>true],
            'sql' => "varchar(36) NOT NULL default ''"
        ]
    ]
];

/**
 * Callback-Klasse für Flächencheck-Listing
 */
class tl_flaechencheck_callbacks
{
    /**
     * Formatiert das Label für die Backend-Liste mit Datum-Gruppierung
     */
    public function formatLabel($row, $label)
    {
        $time = date('H:i', $row['tstamp']);

        // Fallback für alte Einträge ohne status-Spalte
        if (isset($row['status'])) {
            if ($row['status'] === 'success') {
                $status = '✅ Erfolgreich';
                $statusColor = 'green';
            } elseif ($row['status'] === 'failed_with_rating') {
                $status = '🔍 Bewertung verfügbar';
                $statusColor = 'orange';
            } else {
                $status = '❌ Fehlgeschlagen';
                $statusColor = 'red';
            }

            return sprintf(
                '<strong>%s Uhr</strong> - %s %s (%s) <span style="color: %s; font-weight: bold;">[%s]</span>',
                $time,
                $row['vorname'] ?: 'Unbekannt', 
                $row['name'] ?: 'Unbekannt', 
                $row['searched_address'] ?: 'Keine Adresse',
                $statusColor,
                $status
            );
        } else {
            // Alte Format ohne Status (für Rückwärtskompatibilität)
            return sprintf(
                '<strong>%s Uhr</strong> - %s %s (%s)', 
                $time,
                $row['vorname'] ?: 'Unbekannt', 
                $row['name'] ?: 'Unbekannt', 
                $row['searched_address'] ?: 'Keine Adresse'
            );
        }
    }
}