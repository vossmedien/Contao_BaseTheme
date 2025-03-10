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
 * Table tl_caeli_content_creator
 */
$GLOBALS['TL_DCA']['tl_caeli_content_creator'] = array(
    'config'      => array(
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'onsubmit_callback' => [
            // Hier rufen wir den Callback für die Inhaltsgenerierung auf, wenn der Button geklickt wurde
            ['CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator', 'onSubmitGenerateContent']
        ],
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
        '__selector__' => array('addSubpalette'),
        'default'      => '{api_legend},title,grokApiKey,grokApiEndpoint;{content_generation_legend},newsArchive,contentElement,topic,targetAudience,emphasis,tags;{preview_legend},generateButton,previewView;{advanced_settings_legend},addSubpalette'
    ),
    'subpalettes' => array(
        'addSubpalette' => 'additionalInstructions',
    ),
    'fields'      => array(
        'id'             => array(
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ),
        'tstamp'         => array(
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ),
        'title'          => array(
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'grokApiKey'     => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'decodeEntities' => true),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'grokApiEndpoint' => array(
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50', 'decodeEntities' => true),
            'sql'       => "varchar(255) NOT NULL default 'https://api.x.ai/v1'"
        ),
        'newsArchive'    => array(
            'inputType'        => 'select',
            'exclude'          => true,
            'foreignKey'       => 'tl_news_archive.title',
            'eval'             => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'),
            'sql'              => "int(10) unsigned NOT NULL default '0'",
            'relation'         => array('type' => 'hasOne', 'load' => 'lazy')
        ),
        'contentElement' => array(
            'inputType'        => 'select',
            'exclude'          => true,
            'options_callback' => array('CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator', 'getContentElements'),
            'eval'             => array('mandatory' => true, 'chosen' => true, 'tl_class' => 'w50'),
            'sql'              => "varchar(255) NOT NULL default ''"
        ),
        'topic'          => array(
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'eval'      => array('mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'targetAudience' => array(
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'emphasis'       => array(
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'tags'           => array(
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'eval'      => array('mandatory' => false, 'maxlength' => 255, 'tl_class' => 'w50'),
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'min_words'      => array(
            'inputType' => 'text',
            'exclude'   => true,
            'default'   => 300,
            'eval'      => array('rgxp' => 'natural', 'tl_class' => 'w50'),
            'sql'       => "int(10) unsigned NOT NULL default '300'"
        ),
        'include_sources' => array(
            'inputType' => 'checkbox',
            'exclude'   => true,
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'"
        ),
        'add_target_blank' => array(
            'inputType' => 'checkbox',
            'exclude'   => true,
            'eval'      => array('tl_class' => 'w50 m12'),
            'sql'       => "char(1) NOT NULL default '1'"
        ),
        'addSubpalette'  => array(
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('submitOnChange' => true, 'tl_class' => 'w50 clr'),
            'sql'       => "char(1) NOT NULL default ''"
        ),
        'additionalInstructions' => array(
            'inputType' => 'textarea',
            'exclude'   => true,
            'search'    => true,
            'eval'      => array('tl_class' => 'clr', 'rows' => 8),
            'sql'       => 'text NULL'
        ),
        // Der "Generate"-Button - tatsächlich ein verstecktes Feld mit Submit-Button
        'generateButton' => array(
            'input_field_callback' => array('CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator', 'generateButtonCallback'),
            'eval'      => array('tl_class' => 'clr'),
            'exclude'   => true
        ),
        // Vorschau-Anzeige - tatsächlich ein verstecktes Feld mit Anzeige
        'previewView' => array(
            'input_field_callback' => array('CaeliWind\ContaoCaeliContentCreator\DataContainer\CaeliContentCreator', 'previewViewCallback'),
            'eval'      => array('tl_class' => 'clr'),
            'exclude'   => true
        ),
        // Speicherfelder für die Vorschau
        'previewTitle'  => array(
            'sql'       => "varchar(255) NOT NULL default ''"
        ),
        'previewTeaser' => array(
            'sql'       => 'text NULL'
        ),
        'previewContent' => array(
            'sql'       => 'text NULL'
        ),
        'previewTags'   => array(
            'sql'       => "varchar(255) NOT NULL default ''"
        )
    )
);
