<?php

namespace bl\entity\generators\base;

/**
 * Utility class for image processing operations
 * Used by entity and deposit generators
 */
class ImageProcessor
{
    /**
     * Remove background using flood-fill algorithm from corners
     * @param string $imagePath
     * @param int $brightnessThreshold Threshold for background detection (default: 200)
     */
    public static function removeBackground(string $imagePath, int $brightnessThreshold = 200): void
    {
        $img = imagecreatefrompng($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        imagealphablending($img, false);
        imagesavealpha($img, true);

        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        $toProcess = [];
        $processed = [];

        $corners = [
            [0, 0],
            [$width - 1, 0],
            [0, $height - 1],
            [$width - 1, $height - 1]
        ];

        foreach ($corners as [$x, $y]) {
            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $brightness = ($r + $g + $b) / 3;

            if ($brightness > $brightnessThreshold) {
                $toProcess[] = [$x, $y];
            }
        }

        while (!empty($toProcess)) {
            [$x, $y] = array_shift($toProcess);
            $key = "$x,$y";

            if (isset($processed[$key]) || $x < 0 || $x >= $width || $y < 0 || $y >= $height) {
                continue;
            }

            $rgb = imagecolorat($img, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $brightness = ($r + $g + $b) / 3;

            if ($brightness <= $brightnessThreshold) {
                continue;
            }

            $processed[$key] = true;
            imagesetpixel($img, $x, $y, $transparent);

            $toProcess[] = [$x + 1, $y];
            $toProcess[] = [$x - 1, $y];
            $toProcess[] = [$x, $y + 1];
            $toProcess[] = [$x, $y - 1];
        }

        imagepng($img, $imagePath, 9);
        imagedestroy($img);
    }

    /**
     * Scale image with transparency preservation
     * @param string $imagePath
     * @param int $targetWidth
     * @param int $targetHeight
     */
    public static function scaleImage(string $imagePath, int $targetWidth, int $targetHeight): void
    {
        $src = imagecreatefrompng($imagePath);
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        $dest = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        imagecopyresampled($dest, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

        imagepng($dest, $imagePath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Create damaged version of sprite (darkened with dirt spots)
     * @param string $srcPath
     * @param string $destPath
     */
    public static function createDamaged(string $srcPath, string $destPath): void
    {
        $src = imagecreatefrompng($srcPath);
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;

                if ($alpha == 127) {
                    continue;
                }

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Darken by 30%
                $r = intval($r * 0.7);
                $g = intval($g * 0.7);
                $b = intval($b * 0.7);

                // Random dirt spots
                if (rand(0, 100) < 10) {
                    $dirtFactor = rand(80, 100) / 100;
                    $r = intval($r * $dirtFactor);
                    $g = intval($g * $dirtFactor);
                    $b = intval($b * $dirtFactor);
                }

                // Increase contrast
                $r = max(0, min(255, intval(($r - 128) * 1.2 + 128)));
                $g = max(0, min(255, intval(($g - 128) * 1.2 + 128)));
                $b = max(0, min(255, intval(($b - 128) * 1.2 + 128)));

                $newColor = imagecolorallocatealpha($dest, $r, $g, $b, $alpha);
                imagesetpixel($dest, $x, $y, $newColor);
            }
        }

        imagepng($dest, $destPath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Create blueprint version (blue semi-transparent)
     * @param string $srcPath
     * @param string $destPath
     * @param string|null $orientation For direction arrow
     */
    public static function createBlueprint(string $srcPath, string $destPath, ?string $orientation = null): void
    {
        $src = imagecreatefrompng($srcPath);
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;

                if ($alpha == 127) {
                    continue;
                }

                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = ($rgb) & 0xFF;

                // Blue tint
                $r = intval($r * 0.3);
                $g = intval($g * 0.5);
                $b = intval(min(255, $b * 0.8 + 100));

                // Increase transparency
                $alpha = min(127, $alpha + 64);

                $newColor = imagecolorallocatealpha($dest, $r, $g, $b, $alpha);
                imagesetpixel($dest, $x, $y, $newColor);
            }
        }

        // Draw direction arrow for conveyors and manipulators
        if ($orientation && self::needsDirectionArrow($orientation)) {
            self::drawDirectionArrow($dest, $orientation);
        }

        imagepng($dest, $destPath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Create selected version (with yellow outline)
     * @param string $srcPath
     * @param string $destPath
     */
    public static function createSelected(string $srcPath, string $destPath): void
    {
        $src = imagecreatefrompng($srcPath);
        $width = imagesx($src);
        $height = imagesy($src);

        $dest = imagecreatetruecolor($width, $height);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);

        imagecopy($dest, $src, 0, 0, 0, 0, $width, $height);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = imagecolorat($src, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;

                if ($alpha == 127) {
                    continue;
                }

                // Check for transparent neighbors (edge detection)
                $isEdge = false;
                for ($dx = -2; $dx <= 2; $dx++) {
                    for ($dy = -2; $dy <= 2; $dy++) {
                        if ($dx == 0 && $dy == 0) continue;

                        $nx = $x + $dx;
                        $ny = $y + $dy;

                        if ($nx < 0 || $nx >= $width || $ny < 0 || $ny >= $height) {
                            $isEdge = true;
                            break 2;
                        }

                        $neighborRgb = imagecolorat($src, $nx, $ny);
                        $neighborAlpha = ($neighborRgb >> 24) & 0x7F;

                        if ($neighborAlpha == 127) {
                            $isEdge = true;
                            break 2;
                        }
                    }
                }

                if ($isEdge) {
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;

                    $r = min(255, $r + 100);
                    $g = min(255, $g + 100);
                    $b = max(0, $b - 20);

                    $newAlpha = max(0, $alpha - 30);

                    $newColor = imagecolorallocatealpha($dest, $r, $g, $b, $newAlpha);
                    imagesetpixel($dest, $x, $y, $newColor);
                }
            }
        }

        imagepng($dest, $destPath, 9);
        imagedestroy($src);
        imagedestroy($dest);
    }

    /**
     * Rotate image by angle
     * @param string $srcPath
     * @param string $destPath
     * @param int $angle Rotation angle (90, 180, 270)
     */
    public static function rotateImage(string $srcPath, string $destPath, int $angle): void
    {
        $src = imagecreatefrompng($srcPath);

        $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);
        $rotated = imagerotate($src, $angle, $transparent);

        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        imagepng($rotated, $destPath, 9);
        imagedestroy($src);
        imagedestroy($rotated);
    }

    /**
     * Check if orientation needs direction arrow
     */
    private static function needsDirectionArrow(string $orientation): bool
    {
        return strpos($orientation, 'conveyor') === 0 || strpos($orientation, 'manipulator') === 0;
    }

    /**
     * Draw direction arrow on blueprint sprite
     */
    private static function drawDirectionArrow($img, string $orientation): void
    {
        $width = imagesx($img);
        $height = imagesy($img);

        $direction = 'right';
        if (strpos($orientation, '_up') !== false) {
            $direction = 'up';
        } elseif (strpos($orientation, '_down') !== false) {
            $direction = 'down';
        } elseif (strpos($orientation, '_left') !== false) {
            $direction = 'left';
        }

        $arrowColor = imagecolorallocatealpha($img, 255, 220, 0, 30);

        $centerX = intval($width / 2);
        $centerY = intval($height / 2);
        $arrowLength = intval(min($width, $height) * 0.4);
        $arrowWidth = intval($arrowLength * 0.6);

        switch ($direction) {
            case 'right':
                $points = [
                    $centerX - intval($arrowLength / 2), $centerY - intval($arrowWidth / 2),
                    $centerX - intval($arrowLength / 2), $centerY + intval($arrowWidth / 2),
                    $centerX + intval($arrowLength / 2), $centerY,
                ];
                break;
            case 'left':
                $points = [
                    $centerX + intval($arrowLength / 2), $centerY - intval($arrowWidth / 2),
                    $centerX + intval($arrowLength / 2), $centerY + intval($arrowWidth / 2),
                    $centerX - intval($arrowLength / 2), $centerY,
                ];
                break;
            case 'up':
                $points = [
                    $centerX - intval($arrowWidth / 2), $centerY + intval($arrowLength / 2),
                    $centerX + intval($arrowWidth / 2), $centerY + intval($arrowLength / 2),
                    $centerX, $centerY - intval($arrowLength / 2),
                ];
                break;
            case 'down':
                $points = [
                    $centerX - intval($arrowWidth / 2), $centerY - intval($arrowLength / 2),
                    $centerX + intval($arrowWidth / 2), $centerY - intval($arrowLength / 2),
                    $centerX, $centerY + intval($arrowLength / 2),
                ];
                break;
            default:
                return;
        }

        imagefilledpolygon($img, $points, 3, $arrowColor);
    }
}
