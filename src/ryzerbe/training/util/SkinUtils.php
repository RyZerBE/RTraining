<?php


namespace ryzerbe\training\util;


class SkinUtils
{

    /**
     * @param string $filePath
     * @return string
     */
    public static function readImage(string $filePath): string {
        if(!is_file($filePath))
            return "";

        $image = imagecreatefrompng($filePath);
        $fileContent = '';
        for($y = 0, $height = imagesy($image); $y < $height; $y++){
            for($x = 0, $width = imagesx($image); $x < $width; $x++){
                $color = imagecolorat($image, $x, $y);
                $fileContent .= pack("c", ($color >> 16) & 0xFF) //red
                    .pack("c", ($color >> 8) & 0xFF) //green
                    .pack("c", $color & 0xFF) //blue
                    .pack("c", 255 - (($color & 0x7F000000) >> 23)); //alpha
            }
        }
        imagedestroy($image);
        return $fileContent;
    }

}