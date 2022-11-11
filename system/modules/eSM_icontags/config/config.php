<?php

/**
 * eSales Media Icontags for Contao Open Source CMS
 *
 * Copyright (C) 2015 eSalesMedia
 *
 * @package    eSM_icontags
 * @link       http://www.esales-media.de
 * @license    http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 *
 * @author     Benjamin Roth <benjamin@esales-media.de>
 */

$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('\\IcontagsHooks','eSMReplaceInsertTags');