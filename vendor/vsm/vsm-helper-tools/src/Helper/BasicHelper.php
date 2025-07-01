<?php

declare(strict_types=1);

/*
 * This file is part of VSM Helper und Integrations.
 *
 * (c) Vossmedien - Christian Voss 2025 <christian@vossmedien.de>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/vsm/vsm-helper-tools
 */
namespace Vsm\VsmHelperTools\Helper;

use Contao\FilesModel;
use Vsm\VsmHelperTools\Helper\Traits\HelperTrait;
use Vsm\VsmHelperTools\Helper\Traits\DebuggableTrait;
use Vsm\VsmHelperTools\Helper\Exception\HelperException;

/**
 * Basic Helper
 * 
 * Sammlung von grundlegenden Utility-Methoden für allgemeine Aufgaben
 * wie Farbbereinigung und Dateiinformationen.
 */
class BasicHelper
{
    use HelperTrait;
    use DebuggableTrait;

    /**
     * Bereinigt Farbwerte von HTML-Entities und ungültigen Zeichen
     * 
     * @param string|null $color Der zu bereinigende Farbwert
     * @return string Der bereinigte Farbwert oder leerer String
     */
    public static function cleanColor($color): string
    {
        if ($color === null || $color === '') {
            return '';
        }
        
        $search = ["&#41;", "&#40;", "(;", "&#35;", ");"];
        $replace = [")", "(", "(", "#", ")"];
        
        return str_replace($search, $replace, (string)$color);
    }



    /**
     * Ermittelt Dateiinformationen für eine UUID
     * 
     * @param string|null $uuid Die UUID der Datei
     * @return array Array mit 'filename' und 'ext' Keys
     */
    public static function getFileInfo($uuid): array
    {
        if ($uuid === null || $uuid === '') {
            return ['filename' => '', 'ext' => ''];
        }
        
        try {
            $filesModel = FilesModel::findByUuid($uuid);
            if ($filesModel !== null) {
                return [
                    'filename' => $filesModel->path,
                    'ext' => pathinfo($filesModel->path, PATHINFO_EXTENSION)
                ];
            }
        } catch (\Exception $e) {
            // Bei Fehlern leere Werte zurückgeben
            self::logError('Fehler beim Laden der Dateiinformationen', [
                'uuid' => $uuid,
                'error' => $e->getMessage()
            ]);
        }
        
        return ['filename' => '', 'ext' => ''];
    }
}