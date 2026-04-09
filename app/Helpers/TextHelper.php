<?php
namespace App\Helpers;

/**
 * Class Helper untuk fungsi-fungsi yang dapat digunakan secara umum di Intertnal Platform
 */
class TextHelper {
    /**
     * untuk format angka sesuai setting di file config/app.php
     * contoh penggunaan: Texthelper::limitWordsByChar('Ini adalah contoh kalimat panjang untuk testing', 30)
     */
    public static function limitWordsByChar($text, $limit = 25) {
        $words = explode(' ', $text);
        $result = '';
        
        foreach ($words as $word) {
            if (strlen($result . ' ' . $word) > $limit)
                break;
            $result .= ($result ? ' ' : '') . $word;
        }
        return $result;
    }

    public static function getInitials($name){
        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (!empty($word)) 
                $initials .= strtoupper($word[0]);
            if (strlen($initials) == 2)
                break;
        }
        return $initials;
    }
}