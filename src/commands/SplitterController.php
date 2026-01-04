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
     * Creates Y-shaped splitter from conveyor sprite
     * @param string $srcFile Path to source conveyor sprite
     * @return resource Y-shaped image
     */
    private function createYShaped($srcFile)
    {
        $conveyor = imagecreatefrompng($srcFile);
        $width = imagesx($conveyor);
        $height = imagesy($conveyor);

        // Create 64x64 canvas
        $result = imagecreatetruecolor(64, 64);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Fill with transparent
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Extract right half (input)
        $halfWidth = intval($width / 2);
        $rightHalf = imagecreatetruecolor($halfWidth, $height);
        imagealphablending($rightHalf, false);
        imagesavealpha($rightHalf, true);
        imagecopy($rightHalf, $conveyor, 0, 0, $halfWidth, 0, $halfWidth, $height);

        // Place input at top (rotated 180°)
        $inputRotated = imagerotate($rightHalf, 180, $transparent);
        imagealphablending($inputRotated, false);
        imagesavealpha($inputRotated, true);
        imagecopy($result, $inputRotated, 16, 0, 0, 0, imagesx($inputRotated), imagesy($inputRotated));

        // Place left output (rotated 90° CCW)
        $leftOutput = imagerotate($rightHalf, 90, $transparent);
        imagealphablending($leftOutput, false);
        imagesavealpha($leftOutput, true);
        imagecopy($result, $leftOutput, 0, 32, 0, 0, imagesx($leftOutput), imagesy($leftOutput));

        // Place right output (rotated 90° CW)
        $rightOutput = imagerotate($rightHalf, -90, $transparent);
        imagealphablending($rightOutput, false);
        imagesavealpha($rightOutput, true);
        imagecopy($result, $rightOutput, 32, 32, 0, 0, imagesx($rightOutput), imagesy($rightOutput));

        imagedestroy($conveyor);
        imagedestroy($rightHalf);
        imagedestroy($inputRotated);
        imagedestroy($leftOutput);
        imagedestroy($rightOutput);

        return $result;
    }
}
