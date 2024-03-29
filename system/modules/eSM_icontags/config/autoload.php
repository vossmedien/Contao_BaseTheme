<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'eSM_icontags',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Hooks
	'eSM_icontags\IcontagsHooks' => 'system/modules/eSM_icontags/hooks/IcontagsHooks.php',
));
