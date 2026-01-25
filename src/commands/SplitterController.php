<?php

namespace commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Splitter sprite generation commands
 * Handles Y-shaped splitter sprite generation
 */
class SplitterController extends Controller
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
     * Generates Y-shaped splitter sprites
     * Creates 3 types: normal (100), dual (100), fast (200)
     * Usage: php yii splitter/generate
     */
    public function actionGenerate()
    {
        $this->stdout("=== Generate Y-Shaped Splitter Sprites ===\n\n", Console::FG_CYAN);

        // Base sprites to use
        $conveyorPath = $this->entityDir . '/conveyor';

        if (!file_exists($conveyorPath . '/normal.png')) {
            $this->stdout("Error: Base conveyor sprite not found\n", Console::FG_RED);
            return 1;
        }

        $types = [
            'splitter_normal' => 'conveyor',
            'splitter_dual' => 'conveyor_dual',
            'splitter_fast' => 'conveyor_fast',
        ];

        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

        foreach ($types as $splitterFolder => $conveyorFolder) {
            $this->stdout("Generating {$splitterFolder}...\n");

            $srcPath = $this->entityDir . '/' . $conveyorFolder;
            $destPath = $this->entityDir . '/' . $splitterFolder;

            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
                $this->stdout("  Created directory: {$destPath}\n");
            }

            foreach ($states as $state) {
                $srcFile = $srcPath . '/' . $state . '.png';
                $destFile = $destPath . '/' . $state . '.png';

                if (!file_exists($srcFile)) {
                    $this->stdout("  Warning: {$srcFile} not found, skipping\n", Console::FG_YELLOW);
                    continue;
                }

                // Create Y-shaped splitter
                $yShaped = $this->createYShaped($srcFile);
                imagepng($yShaped, $destFile, 9);
                imagedestroy($yShaped);

                $this->stdout("  Generated: {$state}.png\n");
            }

            $this->stdout("  ✓ {$splitterFolder} complete\n\n", Console::FG_GREEN);
        }

        $this->stdout("✓ All splitter sprites generated\n", Console::FG_GREEN);
        $this->stdout("\nNext step: php yii splitter/rotate\n");

        return 0;
    }

    /**
     * Generates animated splitter sprites from animation.png
     * Creates Y-shaped splitters with 8-frame animation
     * Usage: php yii splitter/generate-animation
     */
    public function actionGenerateAnimation()
    {
        $this->stdout("=== Generate Animated Y-Shaped Splitter Sprites ===\n\n", Console::FG_CYAN);

        $conveyorBasePath = $this->entityDir . '/conveyor';

        $types = [
            'splitter_normal' => 'conveyor',
            'splitter_dual' => 'conveyor_dual',
            'splitter_fast' => 'conveyor_fast_dual',
        ];

        foreach ($types as $splitterFolder => $conveyorFolder) {
            $this->stdout("Generating animated {$splitterFolder}...\n");

            $srcPath = $conveyorBasePath . '/' . $conveyorFolder;
            $destPath = $conveyorBasePath . '/' . $splitterFolder;

            if (!is_dir($destPath)) {
                mkdir($destPath, 0755, true);
                $this->stdout("  Created directory: {$destPath}\n");
            }

            // Check if animation.png exists
            $animationFile = $srcPath . '/animation.png';
            if (!file_exists($animationFile)) {
                $this->stdout("  Warning: {$animationFile} not found, skipping\n", Console::FG_YELLOW);
                continue;
            }

            // Load animation (512×64px = 8 frames)
            $animation = imagecreatefrompng($animationFile);
            $animWidth = imagesx($animation);
            $animHeight = imagesy($animation);
            $frameCount = 8;
            $frameWidth = intval($animWidth / $frameCount); // 64px

            $this->stdout("  Source animation: {$animWidth}×{$animHeight}px ({$frameCount} frames)\n");

            // Create result animation
            $resultAnimation = imagecreatetruecolor($animWidth, $animHeight);
            imagealphablending($resultAnimation, false);
            imagesavealpha($resultAnimation, true);
            $transparent = imagecolorallocatealpha($resultAnimation, 0, 0, 0, 127);
            imagefill($resultAnimation, 0, 0, $transparent);

            // Process each frame
            for ($i = 0; $i < $frameCount; $i++) {
                // Extract frame
                $frame = imagecreatetruecolor($frameWidth, $animHeight);
                imagealphablending($frame, false);
                imagesavealpha($frame, true);
                imagefill($frame, 0, 0, $transparent);
                imagecopy($frame, $animation, 0, 0, $i * $frameWidth, 0, $frameWidth, $animHeight);

                // Apply Y-shaped transformation
                $yFrame = $this->createYShapedFromImage($frame);

                // Copy back to result
                imagecopy($resultAnimation, $yFrame, $i * $frameWidth, 0, 0, 0, $frameWidth, $animHeight);

                imagedestroy($frame);
                imagedestroy($yFrame);
            }

            imagedestroy($animation);

            // Save result
            $destFile = $destPath . '/animation.png';
            imagepng($resultAnimation, $destFile, 9);
            imagedestroy($resultAnimation);

            $this->stdout("  ✓ Generated animation.png ({$animWidth}×{$animHeight}px)\n", Console::FG_GREEN);
        }

        $this->stdout("\n✓ All animated splitters generated\n", Console::FG_GREEN);
        $this->stdout("\nNext steps:\n");
        $this->stdout("1. php yii atlas/generate --entity_type_id=800  # splitter_normal\n");
        $this->stdout("2. php yii atlas/generate --entity_type_id=804  # splitter_dual\n");
        $this->stdout("3. php yii atlas/generate --entity_type_id=808  # splitter_fast\n");

        return 0;
    }

    /**
     * Rotates splitter sprites for all orientations
     * Usage: php yii splitter/rotate
     */
    public function actionRotate()
    {
        $this->stdout("=== Rotate Splitter Sprites ===\n\n", Console::FG_CYAN);

        $types = [
            'splitter_normal',
            'splitter_dual',
            'splitter_fast',
        ];

        $orientations = [
            'down' => 90,
            'left' => 180,
            'up' => -90,
        ];

        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

        foreach ($types as $baseFolder) {
            $this->stdout("Processing {$baseFolder}...\n");

            foreach ($orientations as $orientation => $angle) {
                $destFolder = $baseFolder . '_' . $orientation;
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
     * Creates splitter from conveyor image resource
     * Takes full conveyor (input left, output right) and adds top/bottom outputs
     * @param resource $conveyor GD image resource
     * @return resource Splitter image with 3 outputs
     */
    private function createYShapedFromImage($conveyor)
    {
        $width = imagesx($conveyor);
        $height = imagesy($conveyor);

        // Copy full conveyor as base (input left → output right)
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);
        imagecopy($result, $conveyor, 0, 0, 0, 0, $width, $height);

        // Extract right half (output side) for additional outputs
        $halfWidth = intval($width / 2);
        $rightHalf = imagecreatetruecolor($halfWidth, $height);
        imagealphablending($rightHalf, false);
        imagesavealpha($rightHalf, true);
        imagefill($rightHalf, 0, 0, $transparent);
        imagecopy($rightHalf, $conveyor, 0, 0, $halfWidth, 0, $halfWidth, $height);

        // Rotate right half for top output (-90° = clockwise)
        $topOutput = imagerotate($rightHalf, -90, $transparent);
        imagealphablending($topOutput, false);
        imagesavealpha($topOutput, true);

        // Rotate right half for bottom output (90° = counter-clockwise)
        $bottomOutput = imagerotate($rightHalf, 90, $transparent);
        imagealphablending($bottomOutput, false);
        imagesavealpha($bottomOutput, true);

        // Overlay outputs on transparent parts only
        $this->overlayOnTransparent($result, $topOutput, 0, 0);
        $this->overlayOnTransparent($result, $bottomOutput, 0, $height - imagesy($bottomOutput));

        imagedestroy($rightHalf);
        imagedestroy($topOutput);
        imagedestroy($bottomOutput);

        return $result;
    }

    /**
     * Creates splitter from conveyor sprite file
     * Takes full conveyor (input left, output right) and adds top/bottom outputs
     * @param string $srcFile Path to source conveyor sprite
     * @return resource Splitter image with 3 outputs
     */
    private function createYShaped($srcFile)
    {
        $conveyor = imagecreatefrompng($srcFile);
        $result = $this->createYShapedFromImage($conveyor);
        imagedestroy($conveyor);
        return $result;
    }

    /**
     * Overlay source image on destination, only on transparent pixels
     * @param resource $dst Destination image
     * @param resource $src Source image to overlay
     * @param int $dstX Destination X coordinate
     * @param int $dstY Destination Y coordinate
     */
    private function overlayOnTransparent($dst, $src, $dstX, $dstY)
    {
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        for ($y = 0; $y < $srcHeight; $y++) {
            for ($x = 0; $x < $srcWidth; $x++) {
                $dstPixelX = $dstX + $x;
                $dstPixelY = $dstY + $y;

                // Skip if outside destination bounds
                if ($dstPixelX < 0 || $dstPixelX >= imagesx($dst) ||
                    $dstPixelY < 0 || $dstPixelY >= imagesy($dst)) {
                    continue;
                }

                // Get destination pixel alpha
                $dstColor = imagecolorat($dst, $dstPixelX, $dstPixelY);
                $dstAlpha = ($dstColor >> 24) & 0x7F;

                // Only overlay if destination is transparent (alpha > 64)
                if ($dstAlpha > 64) {
                    $srcColor = imagecolorat($src, $x, $y);
                    imagesetpixel($dst, $dstPixelX, $dstPixelY, $srcColor);
                }
            }
        }
    }
}
