<?php

namespace emris\color_analizer;

class ColorAnalyzer
{
    private static $pallet;
    private static $result_array;
    private static $errors;
    private static $image_pixel_data;
    private static $_pixel_step = 1;

    /**
     */
    public static function analyze($file_path, $count_colors)
    {
        self::loadPallete();

        if (!self::getImageData($file_path)) {
            return false;
        }

        foreach (self::$image_pixel_data as $color) {
            self::getMostCloserColorByPallet($color);
        }

        return self::$result_array;
    }

    /**
     * Returns array of colors ich pixel in image
     *
     * @return array
     */
    private static function getImageData($file_path)
    {
        $image = self::getImage($file_path);

        $size   = getimagesize($file_path);
        $width  = $size[0];
        $height = $size[1];
        $colors = [];

        for ($line = 0; $line < $width; $line += self::$_pixel_step) {
            for ($tab = 0; $tab < $height; $tab += self::$_pixel_step) {
                $rgb = imagecolorat($image, $line, $tab);
                $r   = ($rgb >> 16) & 0xFF;
                $g   = ($rgb >> 8) & 0xFF;
                $b   = $rgb & 0xFF;

                $hex = sprintf("#%02x%02x%02x", $r, $g, $b);

                if (isset($colors[$hex])) {
                    $colors[$hex]['count']++;
                } else {
                    $colors[$hex] = [
                        'red'   => $r,
                        'green' => $g,
                        'blue'  => $b,
                        'count' => 1,
                        'hex'   => $hex,
                    ];

                }

            }

        }
        self::$image_pixel_data = $colors;

        return true;
    }

    /**
     * Write in array of the palette with the number of corresponding pixels in the image
     *
     * @return true
     */
    private static function getMostCloserColorByPallet($color)
    {
        $min_diff  = 1000;
        $min_color = null;

        foreach (self::$pallet as $pallet_color) {
            $diff = sqrt(
                pow((int)$pallet_color['rgb']['red'] - (int)$color['red'], 2)
                + pow((int)$pallet_color['rgb']['green'] - (int)$color['green'], 2)
                + pow((int)$pallet_color['rgb']['blue'] - (int)$color['blue'], 2)
            );

            if ((double)$min_diff > (double)$diff) {
                $min_diff  = $diff;
                $min_color = $pallet_color;
            }
        }

        $min_color['count'] = $color['count'];

        if (!empty(self::$result_array)) {

            if (isset(self::$result_array[$min_color['hex']])) {
                self::$result_array[$min_color['hex']]['count'] =
                    (int)self::$result_array[$min_color['hex']]['count'] + (int)$color['count'];

                return true;
            }

        }

        self::$result_array[$min_color['hex']] = $min_color;

        return true;
    }

    /**
     * Returns data of image
     *
     */
    private static function getImage($file_path)
    {
        $file_type = mime_content_type($file_path);

        switch ($file_type) {
            case 'image/jpeg':
                return imagecreatefromjpeg($file_path);
                break;
            case 'image/gd':
                return imagecreatefromgd($file_path);
                break;
            case 'image/gs2':
                return imagecreatefromgd2($file_path);
                break;
            case 'image/gd2part':
                return imagecreatefromgd2part($file_path);
                break;
            case 'image/git':
                return imagecreatefromgif($file_path);
                break;
            case 'image/png':
                return imagecreatefrompng($file_path);
                break;
            case 'image/bmp':
                return imagecreatefrombmp($file_path);
                break;
            default:
                self::$errors = ['Не удалось получить данные изображения'];

                return false;
        }
    }

    /**
     * loads the palette data for further work
     *
     * @return bool
     */
    private static function loadPallete()
    {
        if (file_exists(__DIR__ . '/Pallete.json')) {
            self::$pallet = json_decode(file_get_contents(__DIR__ . '/Pallete.json'), true);

            return true;
        }

        self::$errors = ['Не найден файл палитры'];

        return false;
    }
}
