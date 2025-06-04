<?php

// use Contao\CoreBundle\DataContainer\PaletteManipulator; // Entfernt, da vollqualifiziert verwendet
// use Contao\System; // Entfernt, da vollqualifiziert verwendet

// Parameter abrufen und für Infografik-Download vorbereiten
$imageExtensionsArray = [];
if (\Contao\System::getContainer()) { // Sicherstellen, dass der Container verfügbar ist und vollqualifizierten Klassennamen verwenden
    $imageExtensionsArray = \Contao\System::getContainer()->getParameter('contao.image.valid_extensions');
}
$infographicExtensions = implode(',', array_merge($imageExtensionsArray, ['pdf']));

// Neue Felder für tl_news
$GLOBALS['TL_DCA']['tl_news']['fields']['postAuthor'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['postAuthor'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['authorTitle'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['authorTitle'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['authorImage'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['authorImage'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'filesOnly' => true,
        'fieldType' => 'radio',
        'extensions' => '%contao.image.valid_extensions%',
        'tl_class' => 'clr'
    ],
    'sql' => "binary(16) NULL"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['readingTime'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['readingTime'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 32, 'tl_class' => 'w50'],
    'sql' => "varchar(32) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['articleStatus'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['articleStatus'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['listImage'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['listImage'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'filesOnly' => true,
        'fieldType' => 'radio',
        'extensions' => '%contao.image.valid_extensions%',
        'tl_class' => 'clr'
    ],
    'sql' => "binary(16) NULL"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['detailTitle'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['detailTitle'],
    'exclude' => true,
    'search' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['pdfDownload'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['pdfDownload'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'filesOnly' => true,
        'fieldType' => 'radio',
        'extensions' => 'pdf',
        'tl_class' => 'clr w50',
        'mandatory' => false
    ],
    'sql' => "binary(16) NULL"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['infographicDownload'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['infographicDownload'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'filesOnly' => true,
        'fieldType' => 'radio',
        'extensions' => $infographicExtensions,
        'tl_class' => 'w50',
        'mandatory' => false
    ],
    'sql' => "binary(16) NULL"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['multiSRC'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['multiSRC'],
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => [
        'multiple' => true,
        'fieldType' => 'checkbox',
        'orderField' => 'orderSRC',
        'files' => true,
        'extensions' => '%contao.image.valid_extensions%',
        'isGallery' => true,
        'isSortable' => true,
        'tl_class' => 'clr'
    ],
    'sql' => "blob NULL" 
];

// Feld für die Sortierreihenfolge der Galeriebilder
$GLOBALS['TL_DCA']['tl_news']['fields']['orderSRC'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_news']['orderSRC'],
    'sql' => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['hideAuthorAndNav'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_news']['hideAuthorAndNav'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 m12'],
    'sql'       => "char(1) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['videoSRC'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_news']['videoSRC'],
    'exclude'   => true,
    'inputType' => 'fileTree',
    'eval'      => [
        'multiple'   => false, // Nur ein Video erlauben
        'fieldType'  => 'radio',
        'filesOnly'  => true,
        'extensions' => 'mp4,webm',
        'tl_class'   => 'clr'
    ],
    'sql'       => "binary(16) NULL"
];

$GLOBALS['TL_DCA']['tl_news']['fields']['videoPosterSRC'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_news']['videoPosterSRC'],
    'exclude'   => true,
    'inputType' => 'fileTree',
    'eval'      => [
        'filesOnly'  => true,
        'fieldType'  => 'radio',
        'extensions' => '%contao.image.valid_extensions%',
        'tl_class'   => 'clr'
    ],
    'sql'       => "binary(16) NULL"
];

// Paletten anpassen
\Contao\CoreBundle\DataContainer\PaletteManipulator::create()
    ->addField('detailTitle', 'headline', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)

    ->addLegend('author_legend', 'date_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['postAuthor', 'authorTitle', 'authorImage'], 'author_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    ->addLegend('article_legend', 'author_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['readingTime', 'articleStatus', 'hideAuthorAndNav'], 'article_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    ->addLegend('downloads_legend', 'article_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['pdfDownload', 'infographicDownload'], 'downloads_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    ->addLegend('video_legend', 'image_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_AFTER)
    ->addField(['videoSRC', 'videoPosterSRC'], 'video_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    ->addField('listImage', 'image_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_PREPEND)
    ->addField('multiSRC', 'image_legend', \Contao\CoreBundle\DataContainer\PaletteManipulator::POSITION_APPEND)

    ->applyToPalette('default', 'tl_news');
