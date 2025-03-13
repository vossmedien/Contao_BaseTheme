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

class BasicHelper
{

    public static function cleanColor($color)
    {
        if ($color === null) {
            return '';
        }
        
        $search = ["&#41;", "&#40;", "(;", "&#35;", ");"];
        $replace = [")", "(", "(", "#", ")"];
        return str_replace($search, $replace, $color);
    }



    public static function getFileInfo($uuid)
    {
        if ($uuid === null) {
            return ['filename' => '', 'ext' => ''];
        }
        
        $filesModel = \Contao\FilesModel::findByUuid($uuid);
        if ($filesModel !== null) {
            return [
                'filename' => $filesModel->path,
                'ext' => pathinfo($filesModel->path, PATHINFO_EXTENSION)
            ];
        }
        return ['filename' => '', 'ext' => ''];
    }
}