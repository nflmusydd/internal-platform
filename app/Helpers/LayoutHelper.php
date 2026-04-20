<?php
namespace App\Helpers;

/**
 * Class Helper untuk fungsi-fungsi yang dapat digunakan secara umum di Intertnal Platform
 */
class LayoutHelper {
    public static function convertToViewPath($path){
        if (!$path) return null;

        return str_replace(
            ['resources/views/', '/', '.blade.php'],
            ['', '.', ''],
            $path
        );
    }
}