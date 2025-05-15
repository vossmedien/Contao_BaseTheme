<?php

declare(strict_types=1);

/*
 * This file is part of Caeli KI Content-Creator.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/caeli-wind/contao-caeli-content-creator
 */

use Contao\Backend;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\System;

/**
 * DCA für die Caeli Content Creator Tabelle
 */
$GLOBALS['TL_DCA']['tl_caeli_content_creator'] = array(
    'config' => array(
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'onsubmit_callback' => [
            // Hier rufen wir den Callback für die Inhaltsgenerierung auf, wenn der Button geklickt wurde
            [CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator::class, 'onSubmitGenerateContent']
        ],
        'sql' => array(
            'keys' => array(
                'id' => 'primary'
            )
        )
    ),
    'list' => array(
        'sorting' => array(
                        'mode'        => DataContainer::MODE_SORTABLE,
            'fields'      => array('title'),
            'flag'        => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit'
        ),
        'label' => array(
            'fields' => array('title', 'topic'),
            'showColumns' => true,
            'format' => '%s'
        ),
        'global_operations' => array(
            'all' => array(
                'label' => &$GLOBALS['TL_LANG']['MSC']['all_entrys'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
            )
        ),
        'operations' => array(
            'edit' => array(
                'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ),
            'delete' => array(
                'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? 'Wollen Sie diesen Eintrag wirklich löschen?') . '\'))return false;Backend.getScrollOffset()"'
            ),
            'show' => array(
                'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            )
        )
    ),
    'palettes' => array(
        '__selector__' => [],
        'default' => '{title_legend},title,topic,year,targetAudience,emphasis,add_target_blank,include_sources,additionalInstructions;{content_legend},newsArchive,contentElement,apiKey,apiEndpoint;{ai_params_legend},temperature,topP,min_words;{preview_legend},generateButton,previewView;'
    ),
    'subpalettes' => array(),
    'fields' => array(
        'id' => array(
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp' => array(
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'title' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'topic' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['topic'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'year' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['year'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 100, 'tl_class' => 'w50'),
            'sql' => "varchar(100) NOT NULL default ''"
        ),
        'targetAudience' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['targetAudience'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'emphasis' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['emphasis'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'min_words' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['min_words'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('rgxp' => 'natural', 'tl_class' => 'w50'),
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'include_sources' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['include_sources'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 m12'),
            'sql' => "char(1) NOT NULL default ''"
        ),
        'add_target_blank' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['add_target_blank'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => array('tl_class' => 'w50 m12'),
            'sql' => "char(1) NOT NULL default ''"
        ),
        'additionalInstructions' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['additionalInstructions'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => array('tl_class' => 'clr'),
            'sql' => "text NULL"
        ),
        'newsArchive' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['newsArchive'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_news_archive.title',
            'eval' => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'),
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => array('type' => 'hasOne', 'load' => 'eager')
        ),
        'contentElement' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['contentElement'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => [CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator::class, 'getContentElements'],
            'eval' => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'),
            'sql' => "varchar(64) NOT NULL default ''"
        ),
        'apiKey' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['apiKey'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'apiEndpoint' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['apiEndpoint'],
            'exclude' => true,
            'inputType' => 'select',
            'options' => [
                'https://api.x.ai/v1' => 'X.AI API (Grok)'
            ],
            'default' => 'https://api.x.ai/v1',
            'eval' => array('mandatory' => true, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default 'https://api.x.ai/v1'"
        ),
        'temperature' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['temperature'],
            'exclude' => true,
            'inputType' => 'text',
            'default' => 0.7,
            'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50', 'minval' => 0, 'maxval' => 1),
            'sql' => "decimal(3,2) NOT NULL default '0.70'"
        ),
        'topP' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['topP'],
            'exclude' => true,
            'inputType' => 'text',
            'default' => 0.95,
            'eval' => array('rgxp' => 'digit', 'tl_class' => 'w50', 'minval' => 0, 'maxval' => 1),
            'sql' => "decimal(3,2) NOT NULL default '0.95'"
        ),
        'generateButton' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['generateButton'],
            'exclude' => true,
            'input_field_callback' => [CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator::class, 'generateContentButtonCallback'],
            'eval' => array('doNotShow' => true),
            'sql' => null
        ),
        'previewView' => array(
            'label' => &$GLOBALS['TL_LANG']['tl_caeli_content_creator']['previewView'],
            'exclude' => true,
            'input_field_callback' => [CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator::class, 'previewViewCallback'],
            'eval' => array('doNotShow' => true),
            'sql' => null
        ),
        // Felder für die Vorschau (werden im Frontend nicht angezeigt)
        'previewTitle' => array(
            'eval' => array('doNotShow' => true),
            'sql' => "varchar(255) NOT NULL default ''"
        ),
        'previewTeaser' => array(
            'eval' => array('doNotShow' => true),
            'sql' => "text NULL"
        ),
        'previewContent' => array(
            'eval' => array('doNotShow' => true),
            'sql' => "text NULL"
        ),
        'previewTags' => array(
            'eval' => array('doNotShow' => true),
            'sql' => "varchar(255) NOT NULL default ''"
        )
    )
);
