<?php

declare(strict_types=1);

/*
 * This file is part of Caeli AB Test.
 *
 * (c) Caeli Wind - Christian Voss 2025 <christian.voss@caeli-wind.de>
 * @license MIT
 */

namespace Vsm\VsmAbTest\Helper;

class RockSolidConfigHelper
{
    /**
     * Erweitert RockSolid Custom Element Config um A/B Test Felder
     */
    public static function addAbTestFields(array $config): array
    {
        if (!isset($config['fields'])) {
            $config['fields'] = [];
        }

        // A/B Test Felder hinzufügen (immer am Ende)
        $config['fields']['abtest_section_start'] = [
            'label' => ['A/B Test Einstellungen'],
            'inputType' => 'group',
        ];

        $config['fields']['enableAbTest'] = [
            'label' => ['A/B Test aktivieren', 'Aktivieren Sie diese Option, um das Element in einen A/B Test einzubeziehen.'],
            'inputType' => 'checkbox',
            'eval' => [
                'submitOnChange' => true,
                'tl_class' => 'w50 clr'
            ],
        ];

        $config['fields']['abTestVariant'] = [
            'label' => ['Test-Variante', 'Wählen Sie die Test-Variante für dieses Element aus.'],
            'inputType' => 'select',
            'options' => [
                'test_a' => 'Test A',
                'test_b' => 'Test B',
                'test_c' => 'Test C'
            ],
            'eval' => [
                'includeBlankOption' => true,
                'tl_class' => 'w50'
            ],
            'dependsOn' => [
                'field' => 'enableAbTest',
            ],
        ];

        return $config;
    }
} 