<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

// Fields
$GLOBALS['TL_LANG']['tl_page']['enableAbTest'] = ['A/B Test aktivieren', 'Aktivieren Sie diese Option, um die Seite in einen A/B Test einzubeziehen.'];
$GLOBALS['TL_LANG']['tl_page']['abTestGroup'] = ['A/B Test Gruppe', 'Geben Sie einen eindeutigen Namen für diese Test-Gruppe ein. Seiten mit der gleichen Gruppe sind Alternativen zueinander.'];
$GLOBALS['TL_LANG']['tl_page']['abTestVariant'] = ['Test-Variante', 'Wählen Sie die Test-Variante für diese Seite aus.'];

// Legends
$GLOBALS['TL_LANG']['tl_page']['abtest_legend'] = 'A/B Test Einstellungen';

// Options
$GLOBALS['TL_LANG']['tl_page']['abTestVariants'] = [
    'test_a' => 'Test A',
    'test_b' => 'Test B',
    'test_c' => 'Test C'
]; 