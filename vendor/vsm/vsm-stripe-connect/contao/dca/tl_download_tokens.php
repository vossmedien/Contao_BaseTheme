<?php

declare(strict_types=1);

/*
 * VSM Helper Tools Bundle for Contao Open Source CMS.
 */

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_download_tokens'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'token' => 'unique',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 2,
            'fields' => ['tstamp DESC'],
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['token', 'customer_email', 'order_id', 'expires'],
            'format' => '%s - %s (Bestellung: %s, gültig bis: %s)',
            'label_callback' => ['tl_download_tokens', 'formatLabel'],
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '{token_legend},token,file_id;{customer_legend},order_id,customer_email;{download_legend},expires,download_limit,download_count',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['tstamp'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'sorting' => true,
            'flag' => 6, // Zeigt das Datum in menschenlesbarem Format
        ],
        'token' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['token'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'file_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['file_id'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'mandatory' => true, 'tl_class' => 'w50'],
            'sql' => "binary(16) NULL",
        ],
        'file_path' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['file_path'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'download_file' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['download_file'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'expires' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['expires'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'expires_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['expires_at'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'download_limit' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['download_limit'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '3'",
        ],
        'download_count' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['download_count'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'readonly' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'order_id' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['order_id'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'file_uuid' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['file_uuid'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'created_at' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['created_at'],
            'exclude' => true,
            'sorting' => true,
            'flag' => 6,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'readonly' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'customer_email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['customer_email'],
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'last_download' => [
            'label' => &$GLOBALS['TL_LANG']['tl_download_tokens']['last_download'],
            'exclude' => true,
            'sorting' => true,
            'flag' => 6,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'readonly' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
    ],
];

/**
 * Class tl_download_tokens
 */
class tl_download_tokens extends Contao\Backend
{
    /**
     * Format the label for display in the backend
     */
    public function formatLabel($row, $label, $dc, $args)
    {
        // Formatiere das Ablaufdatum
        $args[3] = $row['expires'] ? date('d.m.Y H:i', (int) $row['expires']) : '-';
        
        // Token kürzen, wenn zu lang
        if (strlen($row['token']) > 10) {
            $args[0] = substr($row['token'], 0, 10) . '...';
        }
        
        return $args;
    }
} 