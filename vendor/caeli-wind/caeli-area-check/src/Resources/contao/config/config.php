<?php

declare(strict_types=1);

// Backend-Module für Flächencheck-Daten
$GLOBALS['BE_MOD']['caeli']['flaechencheck'] = [
    'tables' => ['tl_flaechencheck'],
    'export_data' => ['tl_flaechencheck_callbacks', 'exportData']
]; 