<?php

namespace VSM_HelperFunctions;

class BasicHelper
{

    public static function cleanColor($color)
    {
        $search = ["&#41;", "&#40;", "(;", "&#35;", ");"];
        $replace = [")", "(", "(", "#", ")"];
        return str_replace($search, $replace, $color);
    }



    public static function getFileInfo($uuid)
    {
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