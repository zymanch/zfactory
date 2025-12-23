<?php

namespace helpers;

class VariationGenerator
{
    /**
     * Генерирует N вариаций изображения
     *
     * @param resource $baseImage GD image resource
     * @param int $count Количество вариаций (включая оригинал)
     * @return array Массив GD image resources
     */
    public static function generateVariations($baseImage, $count = 5)
    {
        $width = imagesx($baseImage);
        $height = imagesy($baseImage);
        $variations = [];

        // Первая вариация - оригинал
        $variations[] = self::cloneImage($baseImage);

        // Генерируем остальные вариации
        for ($i = 1; $i < $count; $i++) {
            $variation = self::cloneImage($baseImage);

            // Применяем subtle изменения
            $variation = self::applyColorShift($variation, $i);
            $variation = self::applyNoise($variation, $i);

            $variations[] = $variation;
        }

        return $variations;
    }

    /**
     * Клонирует GD изображение
     */
    private static function cloneImage($source)
    {
        $width = imagesx($source);
        $height = imagesy($source);

        $clone = imagecreatetruecolor($width, $height);
        imagealphablending($clone, false);
        imagesavealpha($clone, true);

        imagecopy($clone, $source, 0, 0, 0, 0, $width, $height);

        return $clone;
    }

    /**
     * Применяет небольшой сдвиг цвета
     */
    private static function applyColorShift($image, $seed)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // Небольшие сдвиги hue/saturation/brightness
        $hueShift = (($seed * 7) % 20) - 10; // -10 до +10
        $satShift = (($seed * 11) % 10) - 5; // -5 до +5
        $brightShift = (($seed * 13) % 10) - 5; // -5 до +5

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($image, $x, $y);
                $alpha = ($rgb >> 24) & 0xFF;

                // Пропускаем полностью прозрачные пиксели
                if ($alpha == 127) {
                    continue;
                }

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Применяем сдвиги
                $r = max(0, min(255, $r + $brightShift));
                $g = max(0, min(255, $g + $brightShift));
                $b = max(0, min(255, $b + $brightShift));

                $newColor = imagecolorallocatealpha($image, $r, $g, $b, $alpha);
                imagesetpixel($image, $x, $y, $newColor);
            }
        }

        return $image;
    }

    /**
     * Добавляет subtle шум для разнообразия
     */
    private static function applyNoise($image, $seed)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        mt_srand($seed * 12345);

        // Добавляем шум только к ~5% пикселей
        $noiseAmount = (int)($width * $height * 0.05);

        for ($i = 0; $i < $noiseAmount; $i++) {
            $x = mt_rand(0, $width - 1);
            $y = mt_rand(0, $height - 1);

            $rgb = imagecolorat($image, $x, $y);
            $alpha = ($rgb >> 24) & 0xFF;

            // Пропускаем прозрачные пиксели
            if ($alpha == 127) {
                continue;
            }

            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;

            // Небольшой шум ±3
            $r = max(0, min(255, $r + mt_rand(-3, 3)));
            $g = max(0, min(255, $g + mt_rand(-3, 3)));
            $b = max(0, min(255, $b + mt_rand(-3, 3)));

            $newColor = imagecolorallocatealpha($image, $r, $g, $b, $alpha);
            imagesetpixel($image, $x, $y, $newColor);
        }

        return $image;
    }
}
