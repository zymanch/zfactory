<?php

namespace commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Manipulator sprite generation commands
 * Handles filtered manipulator sprite generation with HSL color shifts
 */
class ManipulatorController extends Controller
{
    private $basePath;
    private $entityDir;

    public function init()
    {
        parent::init();
        $this->basePath = Yii::getAlias('@app/..');
        $this->entityDir = $this->basePath . '/public/assets/tiles/entities';
    }

    /**
     * Generates sprites for filtered manipulators
     * Creates 3 types with color shifts:
     * - 1F (216): Hue +30° (yellow tint)
     * - 5F (220): Hue +60° (orange tint)
     * - Counting (221): Hue +90° (green tint)
     *
     * Usage: php yii manipulator/generate-filtered
     */
    public function actionGenerateFiltered()
    {
        $this->stdout("=== Generate Filtered Manipulator Sprites ===\n\n", Console::FG_CYAN);

        $basePath = $this->entityDir . '/manipulator_short';

        if (!file_exists($basePath . '/normal.png')) {
            $this->stdout("Error: Base manipulator sprite not found\n", Console::FG_RED);
            return 1;
        }

        // Типы: название папки => hue shift в градусах
        $types = [
            'manipulator_filtered_1f' => 30,    // Yellow
            'manipulator_filtered_5f' => 60,    // Orange
            'manipulator_counting_1f' => 90,    // Green
        ];

        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

        foreach ($types as $folder => $hueShift) {
            $this->stdout("Generating {$folder} (hue +{$hueShift}°)...\n");

            $destPath = $this->entityDir . '/' . $folder;
            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
                $this->stdout("  Created directory: {$destPath}\n");
            }

            foreach ($states as $state) {
                $srcFile = $basePath . '/' . $state . '.png';
                $destFile = $destPath . '/' . $state . '.png';

                if (!file_exists($srcFile)) {
                    $this->stdout("  Warning: {$srcFile} not found, skipping\n", Console::FG_YELLOW);
                    continue;
                }

                // Load, apply HSL shift, save
                $img = imagecreatefrompng($srcFile);
                imagealphablending($img, false);
                imagesavealpha($img, true);

                $shifted = $this->applyHueShift($img, $hueShift);

                imagepng($shifted, $destFile, 9);
                imagedestroy($img);
                imagedestroy($shifted);

                $this->stdout("  Generated: {$state}.png\n");
            }

            $this->stdout("  ✓ {$folder} complete\n\n", Console::FG_GREEN);
        }

        $this->stdout("✓ All filtered manipulator sprites generated\n", Console::FG_GREEN);
        $this->stdout("\nNext steps:\n");
        $this->stdout("  1. Create rotated variants for orientations\n");
        $this->stdout("  2. Run: php yii manipulator/rotate-filtered\n");

        return 0;
    }

    /**
     * Rotates filtered manipulator sprites for all orientations
     * Usage: php yii manipulator/rotate-filtered
     */
    public function actionRotateFiltered()
    {
        $this->stdout("=== Rotate Filtered Manipulators ===\n\n", Console::FG_CYAN);

        $types = [
            'manipulator_filtered_1f',
            'manipulator_filtered_5f',
            'manipulator_counting_1f',
        ];

        $orientations = [
            'down' => 90,
            'left' => 180,
            'up' => -90,
            'right-up' => -45,
            'right-down' => 45,
        ];

        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

        foreach ($types as $baseFolder) {
            $this->stdout("Processing {$baseFolder}...\n");

            foreach ($orientations as $orientation => $angle) {
                $destFolder = $baseFolder . '_' . str_replace('-', '_', $orientation);
                $destPath = $this->entityDir . '/' . $destFolder;

                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }

                foreach ($states as $state) {
                    $srcFile = $this->entityDir . '/' . $baseFolder . '/' . $state . '.png';
                    $destFile = $destPath . '/' . $state . '.png';

                    if (!file_exists($srcFile)) {
                        continue;
                    }

                    $src = imagecreatefrompng($srcFile);
                    $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);
                    $rotated = imagerotate($src, -$angle, $transparent);
                    imagealphablending($rotated, false);
                    imagesavealpha($rotated, true);

                    imagepng($rotated, $destFile, 9);

                    imagedestroy($src);
                    imagedestroy($rotated);
                }

                $this->stdout("  ✓ {$destFolder}\n");
            }
        }

        $this->stdout("\n✓ All orientations generated\n", Console::FG_GREEN);

        return 0;
    }

    /**
     * Applies HSL hue shift to an image
     * @param resource $img Source image
     * @param int $hueShift Hue shift in degrees (0-360)
     * @return resource Shifted image
     */
    private function applyHueShift($img, $hueShift)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                // Convert RGB to HSL
                list($h, $s, $l) = $this->rgbToHsl($r, $g, $b);

                // Shift hue
                $h = fmod($h + $hueShift, 360);
                if ($h < 0) $h += 360;

                // Convert back to RGB
                list($r, $g, $b) = $this->hslToRgb($h, $s, $l);

                $color = imagecolorallocatealpha($result, $r, $g, $b, $alpha);
                imagesetpixel($result, $x, $y, $color);
            }
        }

        return $result;
    }

    /**
     * Convert RGB to HSL
     */
    private function rgbToHsl($r, $g, $b)
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0; // achromatic
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r: $h = (($g - $b) / $d + ($g < $b ? 6 : 0)); break;
                case $g: $h = (($b - $r) / $d + 2); break;
                case $b: $h = (($r - $g) / $d + 4); break;
            }
            $h /= 6;
        }

        return array($h * 360, $s, $l);
    }

    /**
     * Convert HSL to RGB
     */
    private function hslToRgb($h, $s, $l)
    {
        $h /= 360;

        if ($s == 0) {
            $r = $g = $b = $l; // achromatic
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;

            $r = $this->hueToRgb($p, $q, $h + 1/3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1/3);
        }

        return array(round($r * 255), round($g * 255), round($b * 255));
    }

    /**
     * Helper for HSL to RGB conversion
     */
    private function hueToRgb($p, $q, $t)
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
}
