<?php

namespace App;

use Imagick;
use ImagickPixel;

class Helpers
{

    static public function fastImageCopyResampled(&$dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 4) {

        /**
         * Plug-and-Play fastImageCopyResampled function replaces much slower imagecopyresampled.
         * Just include this function and change all "imagecopyresampled" references to "fastImageCopyResampled".
         * Typically from 30 to 60 times faster when reducing high resolution images down to thumbnail size using the default quality setting.
         * Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 - Project: FreeRingers.net - Freely distributable - These comments must remain.
         *
         * Optional "quality" parameter (defaults is 3). Fractional values are allowed, for example 1.5. Must be greater than zero.
         * Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
         * 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
         * 2 = Up to 95 times faster.  Images appear a little sharp, some prefer this over a quality of 3.
         * 3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled, just faster.
         * 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
         * 5 = No speedup. Just uses imagecopyresampled, no advantage over imagecopyresampled.
         */

        if (empty($src_image) || empty($dst_image) || $quality <= 0) { return false; }

        if ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {

            $temp = imagecreatetruecolor($dst_w * $quality + 1, $dst_h * $quality + 1);
            imagecopyresized($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1, $dst_h * $quality + 1, $src_w, $src_h);
            imagecopyresampled($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w, $dst_h, $dst_w * $quality, $dst_h * $quality);
            imagedestroy($temp);

        } else imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

        return true;

    }


//    /**
//     * @return string Generated ID.
//     */
//    static public function generateID() {
//
//        // Generate id based on the current microtime
//        $id = str_replace('.', '', microtime(true));
//
//        // Ensure that the id has a length of 14 chars
//        while(strlen($id)<14) $id .= 0;
//
//        // Return id as a string. Don't convert the id to an integer
//        // as 14 digits are too big for 32bit PHP versions.
//        return $id;
//
//    }



    /**
     * Returns the extension of the filename (path or URI) or an empty string.
     * @return string Extension of the filename starting with a dot.
     */
    static public function getExtension($filename, $isURI = false) {

        // If $filename is an URI, get only the path component
        if ($isURI===true) $filename = parse_url($filename, PHP_URL_PATH);

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // Special cases
        // https://github.com/electerious/Lychee/issues/482
        list($extension) = explode(':', $extension, 2);

        if (empty($extension)===false) $extension = '.' . $extension;

        return $extension;

    }



    /**
     * Returns the normalized coordinate from EXIF array.
     * @return string Normalized coordinate as float number (degrees).
     */
    static public function getGPSCoordinate($coordinate, $ref) {

        $degrees = count($coordinate) > 0 ? Helpers::formattedToFloatGPS($coordinate[0]) : 0;
        $minutes = count($coordinate) > 1 ? Helpers::formattedToFloatGPS($coordinate[1]) : 0;
        $seconds = count($coordinate) > 2 ? Helpers::formattedToFloatGPS($coordinate[2]) : 0;

        $flip = ($ref == 'W' || $ref == 'S') ? -1 : 1;

        return $flip * ($degrees + (float)$minutes / 60 + (float)$seconds / 3600);

    }

    static public function formattedToFloatGPS($coordinate) {

        $parts = explode('/', $coordinate, 2);

        if (count($parts) <= 0) return 0;
        if (count($parts) == 1) return $parts[0];

        return (float)$parts[0] / $parts[1];

    }


    static public function getHashedString($password) {

        // Inspired by http://alias.io/2010/01/store-passwords-safely-with-php-and-mysql/
        // A higher $cost is more secure but consumes more processing power
        $cost = 10;

        // Create a random salt
        if (extension_loaded('openssl')) {
            $salt = strtr(substr(base64_encode(openssl_random_pseudo_bytes(17)),0,22), '+', '.');
        } elseif (extension_loaded('mcrypt')) {
            $salt = strtr(substr(base64_encode(mcrypt_create_iv(17, MCRYPT_DEV_URANDOM)),0,22), '+', '.');
        } else {
            $salt = '';
            for ($i = 0; $i < 22; $i++) {
                $salt .= substr("./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789", mt_rand(0, 63), 1);
            }
        }

        // Prefix information about the hash so PHP knows how to verify it later.
        // "$2a$" Means we're using the Blowfish algorithm. The following two digits are the cost parameter.
        $salt = sprintf("$2a$%02d$", $cost) . $salt;

        // Hash the password with the salt
        return crypt($password, $salt);

    }


    static public function hasPermissions($path) {
        // Check if the given path is readable and writable
        // Both functions are also verifying that the path exists
        if (is_readable($path)===true&&is_writeable($path)===true) return true;
        return false;
    }

}