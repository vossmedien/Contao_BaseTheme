<?php

declare(strict_types=1);

/*
 * This file is part of Caeli Google News Fetcher.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/caeli-google-news-fetch
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\System;

/**
 * Table tl_caeli_googlenews
 */
$GLOBALS['TL_DCA']['tl_caeli_googlenews'] = array(
    'config'      => array(
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'onload_callback' => array(
            array('CaeliWind\CaeliGoogleNewsFetch\DataContainer\GoogleNewsFetcher', 'onLoadCallback')
        ),
        'sql'              => array(
            'keys' => array(
                'id' => 'primary'
            )
        ),
    ),
    'list'        => array(
        'sorting'           => array(
            'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => array('title'),
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ),
        'label'             => array(
            'fields' => array('title'),
            'format' => '%s',
        ),
        'global_operations' => array(
            'all' => array(
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations'        => array(
            'edit'   => array(
                'href'  => 'act=edit',
                'icon'  => 'edit.svg'
            ),
            'copy'   => array(
                'href'  => 'act=copy',
                'icon'  => 'copy.svg'
            ),
            'delete' => array(
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show'   => array(
                'href'       => 'act=show',
                'icon'       => 'show.svg',
                'attributes' => 'style="margin-right:3px"'
            ),
        )
    ),
    'palettes'    => array(
        'default'      => '{title_legend},title,newsArchive;{serpapi_legend},serpApiKey,serpApiQuery,serpApiNumResults,serpApiLocation,serpApiLanguage;{filter_legend},dateRestrict,newsSource,paginationEnabled,maxPages;{keywords_legend},blacklistKeywords;{preview_legend},fetchNewsButton,previewView;'
    ),

    // Buttons im Bearbeitungsformular
    'buttons_callback' => array(
        array('CaeliWind\CaeliGoogleNewsFetch\DataContainer\GoogleNewsFetcher', 'addCustomButton')
    ),

    'fields'      => array(
        'id'                => array(
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp'            => array(
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'title'             => array(
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'newsArchive'       => array(
            'inputType'        => 'select',
            'exclude'          => true,
            'filter'           => true,
            'options_callback' => array('CaeliWind\CaeliGoogleNewsFetch\DataContainer\GoogleNewsFetcher', 'getNewsArchives'),
            'eval'             => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'),
            'sql'              => "int(10) unsigned NOT NULL default '0'"
        ),
        // SerpAPI Einstellungen
        'serpApiKey'       => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'explanation' => 'API-Schlüssel für SerpAPI. Mehr Infos: https://serpapi.com/',
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'serpApiQuery'     => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'explanation' => 'Hauptsuchbegriffe für Google News, nach diesen Begriffen wird gesucht. Mehrere Begriffe mit Leerzeichen trennen.',
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'serpApiNumResults' => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('rgxp' => 'natural', 'tl_class' => 'w50'),
            'default'   => 100,
            'explanation' => 'Maximale Anzahl an Ergebnissen (max. 100 bei SerpAPI)',
            'sql'       => "smallint(5) unsigned NOT NULL default '100'"
        ),
        'serpApiLocation'  => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
            'default'   => 'Germany',
            'explanation' => 'Standort für die Suche, z.B. "Germany", "United States" usw.',
            'sql'       => "varchar(255) NOT NULL default 'Germany'"
        ),
        'serpApiLanguage'  => array(
            'inputType' => 'select',
            'exclude'   => true,
            'options'   => array('de' => 'Deutsch', 'en' => 'Englisch', 'fr' => 'Französisch'),
            'default'   => 'de',
            'eval'      => array('tl_class' => 'w50'),
            'explanation' => 'Sprache für die Suchergebnisse',
            'sql'       => "varchar(2) NOT NULL default 'de'"
        ),
        // Zusätzliche Filter-Optionen
        'dateRestrict'     => array(
            'inputType' => 'select',
            'exclude'   => true,
            'options'   => array(
                '' => 'Alle Zeiten',
                'h' => 'Letzte Stunde',
                'd' => 'Letzter Tag',
                'w' => 'Letzte Woche',
                'm' => 'Letzter Monat',
                'y' => 'Letztes Jahr'
            ),
            'default'   => '',
            'eval'      => array('tl_class' => 'w50'),
            'explanation' => 'Zeitraum für die Suchergebnisse beschränken',
            'sql'       => "varchar(2) NOT NULL default ''"
        ),
        'newsSource'      => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('maxlength' => 255, 'tl_class' => 'w50'),
            'explanation' => 'Filtere nach einer bestimmten Quelle (z.B. "spiegel.de")',
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        // Pagination-Optionen
        'paginationEnabled' => array(
            'inputType' => 'checkbox',
            'exclude'   => true,
            'default'   => '',
            'eval'      => array('tl_class' => 'w50 m12'),
            'explanation' => 'Aktiviert die Paginierung, um mehr als 100 Ergebnisse abzurufen',
            'sql'       => "char(1) NOT NULL default ''"
        ),
        'maxPages'        => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('rgxp' => 'natural', 'tl_class' => 'w50'),
            'default'   => 3,
            'explanation' => 'Maximale Anzahl an Seiten, die abgerufen werden sollen (max. 10)',
            'sql'       => "smallint(5) unsigned NOT NULL default '3'"
        ),
        // Keywords für Filter
        'blacklistKeywords' => array(
            'inputType' => 'textarea',
            'exclude'   => true,
            'eval'      => array('decodeEntities' => true, 'tl_class' => 'clr', 'style' => 'height: 80px'),
            'explanation' => 'Begriffe, die NICHT im Titel oder der Beschreibung vorkommen dürfen (ein Begriff pro Zeile)',
            'sql'       => "text NULL"
        ),
        'lastFetch'        => array(
            'sql'       => "int(10) unsigned NOT NULL default '0'"
        ),
        'fetchNewsButton' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_googlenews']['fetchNewsButton'],
            'exclude' => true,
            'input_field_callback' => [CaeliWind\CaeliGoogleNewsFetch\DataContainer\GoogleNewsFetcher::class, 'generateFetchButtonCallback'],
            'eval' => array('doNotShow' => true),
            'sql' => null
        ),
        'previewView' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_googlenews']['previewView'],
            'exclude' => true,
            'input_field_callback' => [CaeliWind\CaeliGoogleNewsFetch\DataContainer\GoogleNewsFetcher::class, 'previewViewCallback'],
            'eval' => array('doNotShow' => true),
            'sql' => null
        ),
        // Felder für die Vorschau der abgerufenen Artikel (werden im Frontend nicht angezeigt)
        'previewItems' => array(
            'eval' => array('doNotShow' => true),
            'sql' => "text NULL"
        ),
        // Letztes Update-Datum
        'lastUpdated' => array(
            'eval' => array('doNotShow' => true),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        )
    )
);
