<?php

namespace bl\landing\generators\base;

/**
 * Utility class for landing image processing operations
 * Used by landing generators
 */
class LandingImageProcessor
{
    /**
     * Make texture seamless tileable using offset+blend method
     * @param string $imagePath
     */
    public static function makeSeamless(string $imagePath): void
    {
        $img = imagecreatefrompng($imagePath);
        if (!$img) {
            return;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        imagesavealpha($img, true);

        // Create new image
        $seamless = imagecreatetruecolor($width, $height);
        imagesavealpha($seamless, true);

        // Offset by half width and height
        $offsetX = (int)($width / 2);
        $offsetY = (int)($height / 2);

        // Copy in 4 quadrants (offset method)
        imagecopy($seamless, $img, 0, 0, $offsetX, $offsetY, $width - $offsetX, $height - $offsetY);
        imagecopy($seamless, $img, $width - $offsetX, 0, 0, $offsetY, $offsetX, $height - $offsetY);
        imagecopy($seamless, $img, 0, $height - $offsetY, $offsetX, 0, $width - $offsetX, $offsetY);
        imagecopy($seamless, $img, $width - $offsetX, $height - $offsetY, 0, 0, $offsetX, $offsetY);

        imagedestroy($img);

        // Apply Gaussian blur to hide seams
        $blurRadius = 5;
        for ($i = 0; $i < $blurRadius; $i++) {
            imagefilter($seamless, IMG_FILTER_GAUSSIAN_BLUR);
        }

        imagepng($seamless, $imagePath);
        imagedestroy($seamless);
    }

    /**
     * Make bottom portion of image transparent (for edge types)
     * @param string $imagePath
     * @param float $heightPercentage Percentage of height to make transparent (0.0-1.0)
     */
    public static function makeBottomTransparent(string $imagePath, float $heightPercentage = 0.5): void
    {
        $img = self::loadImageAny($imagePath);
        if (!$img) {
            return;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        imagealphablending($img, false);
        imagesavealpha($img, true);

        $startY = (int)($height * (1 - $heightPercentage));

        for ($y = $startY; $y < $height; $y++) {
            $progress = ($y - $startY) / ($height - $startY);
            $alpha = (int)(127 * $progress);

            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;

                $newColor = imagecolorallocatealpha($img, $r, $g, $b, $alpha);
                imagesetpixel($img, $x, $y, $newColor);
            }
        }

        imagepng($img, $imagePath, 9);
        imagedestroy($img);
    }

    /**
     * Scale image to target size using nearest neighbor interpolation
     * @param string $srcPath
     * @param string $destPath
     * @param int $targetWidth
     * @param int $targetHeight
     */
    public static function scaleImage(string $srcPath, string $destPath, int $targetWidth, int $targetHeight): void
    {
        $src = self::loadImageAny($srcPath);
        if (!$src) {
            return;
        }

        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        $dest = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        imagesetinterpolation($dest, IMG_NEAREST_NEIGHBOUR);

        imagecopyresampled(
            $dest,
            $src,
            0, 0, 0, 0,
            $targetWidth, $targetHeight,
            $srcWidth, $srcHeight
        );

        imagepng($dest, $destPath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Load image from any supported format
     * @param string $path
     * @return resource|null
     */
    public static function loadImageAny(string $path)
    {
        if (!file_exists($path)) {
            return null;
        }

        $imageInfo = getimagesize($path);
        $mimeType = $imageInfo['mime'] ?? '';

        switch ($mimeType) {
            case 'image/png':
                $image = imagecreatefrompng($path);
                break;
            case 'image/jpeg':
                $image = imagecreatefromjpeg($path);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($path);
                break;
            default:
                return null;
        }

        if ($image) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image ?: null;
    }

    /**
     * Apply color tint to image
     * @param string $imagePath
     * @param int $r Red component (0-255)
     * @param int $g Green component (0-255)
     * @param int $b Blue component (0-255)
     * @param float $intensity Tint intensity (0.0-1.0)
     */
    public static function applyColorTint(string $imagePath, int $r, int $g, int $b, float $intensity = 0.3): void
    {
        $img = self::loadImageAny($imagePath);
        if (!$img) {
            return;
        }

        $width = imagesx($img);
        $height = imagesy($img);

        imagealphablending($img, false);
        imagesavealpha($img, true);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);
                $alpha = ($color >> 24) & 0x7F;

                if ($alpha == 127) {
                    continue;
                }

                $origR = ($color >> 16) & 0xFF;
                $origG = ($color >> 8) & 0xFF;
                $origB = $color & 0xFF;

                $newR = (int)($origR * (1 - $intensity) + $r * $intensity);
                $newG = (int)($origG * (1 - $intensity) + $g * $intensity);
                $newB = (int)($origB * (1 - $intensity) + $b * $intensity);

                $newColor = imagecolorallocatealpha($img, $newR, $newG, $newB, $alpha);
                imagesetpixel($img, $x, $y, $newColor);
            }
        }

        imagepng($img, $imagePath, 9);
        imagedestroy($img);
    }

    /**
     * Adjust image brightness
     * @param string $imagePath
     * @param int $brightness -255 to 255
     */
    public static function adjustBrightness(string $imagePath, int $brightness): void
    {
        $img = self::loadImageAny($imagePath);
        if (!$img) {
            return;
        }

        imagefilter($img, IMG_FILTER_BRIGHTNESS, $brightness);

        imagepng($img, $imagePath, 9);
        imagedestroy($img);
    }

    /**
     * Adjust image contrast
     * @param string $imagePath
     * @param int $contrast -100 to 100
     */
    public static function adjustContrast(string $imagePath, int $contrast): void
    {
        $img = self::loadImageAny($imagePath);
        if (!$img) {
            return;
        }

        imagefilter($img, IMG_FILTER_CONTRAST, $contrast);

        imagepng($img, $imagePath, 9);
        imagedestroy($img);
    }
}
