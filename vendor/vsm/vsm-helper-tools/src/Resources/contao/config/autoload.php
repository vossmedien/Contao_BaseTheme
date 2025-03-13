<?php

/**
 * Automatisches Laden alter Namespaces
 *
 * Diese Datei stellt sicher, dass Code, der den alten Namespace VSM_HelperFunctions verwendet,
 * weiterhin funktioniert, indem er auf die entsprechenden Klassen im neuen Namespace verweist.
 */

// Helper-Klassen mappen
$helpers = [
    'HeadlineHelper',
    'ImageHelper',
    'VideoHelper',
    'ButtonHelper',
    'BasicHelper',
    'EnvHelper',
    'PaymentFormHelper',
    'GlobalElementConfig'
];

foreach ($helpers as $helper) {
    $oldClass = 'VSM_HelperFunctions\\' . $helper;
    $newClass = 'Vsm\\VsmHelperTools\\Helper\\' . $helper;
    
    if (!class_exists($oldClass) && class_exists($newClass)) {
        class_alias($newClass, $oldClass);
    }
} 