<?php

namespace commands;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Underground conveyor sprite generation commands
 * Handles entrance and exit sprites with semicircular holes
 */
class UndergroundController extends Controller
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
     * Generates underground conveyor sprites (entrances and exits)
     * Creates 6 types: normal in/out, dual in/out, fast in/out
     * Usage: php yii underground/generate
     */
    public function actionGenerate()
    {
        $this->stdout("=== Generate Underground Conveyor Sprites ===\n\n", Console::FG_CYAN);

        $types = [
            ['underground_belt_in', 'conveyor', 'entrance'],
            ['underground_belt_out', 'conveyor', 'exit'],
            ['underground_belt_dual_in', 'conveyor_dual', 'entrance'],
            ['underground_belt_dual_out', 'conveyor_dual', 'exit'],
            ['underground_belt_fast_in', 'conveyor_fast', 'entrance'],
            ['underground_belt_fast_out', 'conveyor_fast', 'exit'],
        ];

        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];

        foreach ($types as list($folder, $conveyorFolder, $type)) {
            $this->stdout("Generating {$folder}...\n");

            $srcPath = $this->entityDir . '/' . $conveyorFolder;
            $destPath = $this->entityDir . '/' . $folder;

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

                // Create underground sprite
                if ($type === 'entrance') {
                    $result = $this->createEntrance($srcFile);
                } else {
                    $result = $this->createExit($srcFile);
                }

                imagepng($result, $destFile, 9);
                imagedestroy($result);

                $this->stdout("  Generated: {$state}.png\n");
            }

            $this->stdout("  ✓ {$folder} complete\n\n", Console::FG_GREEN);
        }

        $this->stdout("✓ All underground conveyor sprites generated\n", Console::FG_GREEN);
        $this->stdout("\nNext step: php yii underground/rotate\n");

        return 0;
    }

    /**
     * Rotates underground conveyor sprites for all orientations
     * Usage: php yii underground/rotate
     */
    public function actionRotate()
    {
        $this->stdout("=== Rotate Underground Conveyor Sprites ===\n\n", Console::FG_CYAN);

        $types = [
            'underground_belt_in',
            'underground_belt_out',
            'underground_belt_dual_in',
            'underground_belt_dual_out',
            'underground_belt_fast_in',
            'underground_belt_fast_out',
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
     * Generates animated underground conveyor sprites from animation.png
     * Creates entrance and exit sprites with 8-frame animation
     * Usage: php yii underground/generate-animation
     */
    public function actionGenerateAnimation()
    {
        $this->stdout("=== Generate Animated Underground Conveyor Sprites ===\n\n", Console::FG_CYAN);

        $conveyorBasePath = $this->entityDir . '/conveyor';

        $types = [
            ['underground_belt_in', 'conveyor', 'entrance'],
            ['underground_belt_out', 'conveyor', 'exit'],
            ['underground_belt_dual_in', 'conveyor_dual', 'entrance'],
            ['underground_belt_dual_out', 'conveyor_dual', 'exit'],
            ['underground_belt_fast_in', 'conveyor_fast_dual', 'entrance'],
            ['underground_belt_fast_out', 'conveyor_fast_dual', 'exit'],
        ];

        foreach ($types as list($folder, $conveyorFolder, $type)) {
            $this->stdout("Generating animated {$folder}...\n");

            $srcPath = $conveyorBasePath . '/' . $conveyorFolder;
            $destPath = $conveyorBasePath . '/' . $folder;

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

                // Apply underground transformation
                if ($type === 'entrance') {
                    $ugFrame = $this->createEntranceFromImage($frame);
                } else {
                    $ugFrame = $this->createExitFromImage($frame);
                }

                // Copy back to result
                imagecopy($resultAnimation, $ugFrame, $i * $frameWidth, 0, 0, 0, $frameWidth, $animHeight);

                imagedestroy($frame);
                imagedestroy($ugFrame);
            }

            imagedestroy($animation);

            // Save result
            $destFile = $destPath . '/animation.png';
            imagepng($resultAnimation, $destFile, 9);
            imagedestroy($resultAnimation);

            $this->stdout("  ✓ Generated animation.png ({$animWidth}×{$animHeight}px)\n", Console::FG_GREEN);
        }

        $this->stdout("\n✓ All animated underground conveyors generated\n", Console::FG_GREEN);
        $this->stdout("\nNext: Regenerate atlases for entity_type_id 812-835\n");

        return 0;
    }

    /**
     * Creates entrance sprite from image resource (left half + dark semicircle on right)
     * @param resource $conveyor GD image resource
     * @return resource Entrance image
     */
    private function createEntranceFromImage($conveyor)
    {
        $width = imagesx($conveyor);
        $height = imagesy($conveyor);

        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Fill with transparent
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Copy left half
        $halfWidth = intval($width / 2);
        imagecopy($result, $conveyor, 0, 0, 0, 0, $halfWidth, $height);

        // Draw dark semicircle on right (entrance hole)
        $centerX = $halfWidth;
        $centerY = intval($height / 2);
        $radius = intval($height / 2) - 4;

        $darkColor = imagecolorallocatealpha($result, 20, 20, 20, 30);

        // Draw filled semicircle (right side)
        for ($x = $centerX; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $dx = $x - $centerX;
                $dy = $y - $centerY;
                $distance = sqrt($dx * $dx + $dy * $dy);

                if ($distance <= $radius) {
                    imagesetpixel($result, $x, $y, $darkColor);
                }
            }
        }

        return $result;
    }

    /**
     * Creates exit sprite from image resource (dark semicircle on left + right half)
     * @param resource $conveyor GD image resource
     * @return resource Exit image
     */
    private function createExitFromImage($conveyor)
    {
        $width = imagesx($conveyor);
        $height = imagesy($conveyor);

        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Fill with transparent
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Copy right half
        $halfWidth = intval($width / 2);
        imagecopy($result, $conveyor, $halfWidth, 0, $halfWidth, 0, $halfWidth, $height);

        // Draw dark semicircle on left (exit hole)
        $centerX = $halfWidth;
        $centerY = intval($height / 2);
        $radius = intval($height / 2) - 4;

        $darkColor = imagecolorallocatealpha($result, 20, 20, 20, 30);

        // Draw filled semicircle (left side)
        for ($x = 0; $x < $centerX; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $dx = $x - $centerX;
                $dy = $y - $centerY;
                $distance = sqrt($dx * $dx + $dy * $dy);

                if ($distance <= $radius) {
                    imagesetpixel($result, $x, $y, $darkColor);
                }
            }
        }

        return $result;
    }

    /**
     * Creates entrance sprite from file (left half + dark semicircle on right)
     * @param string $srcFile Path to source conveyor sprite
     * @return resource Entrance image
     */
    private function createEntrance($srcFile)
    {
        $conveyor = imagecreatefrompng($srcFile);
        $result = $this->createEntranceFromImage($conveyor);
        imagedestroy($conveyor);
        return $result;
    }

    /**
     * Creates exit sprite from file (dark semicircle on left + right half)
     * @param string $srcFile Path to source conveyor sprite
     * @return resource Exit image
     */
    private function createExit($srcFile)
    {
        $conveyor = imagecreatefrompng($srcFile);
        $result = $this->createExitFromImage($conveyor);
        imagedestroy($conveyor);
        return $result;
    }
}
