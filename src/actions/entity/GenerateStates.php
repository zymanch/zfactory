<?php

namespace actions\entity;

use actions\ConsoleAction;
use models\EntityType;
use Yii;

/**
 * Generate blueprint and construction frame sprites for all entities
 * Usage: php yii entity/generate-states
 */
class GenerateStates extends ConsoleAction
{

    public function run()
    {
        $this->stdout("Generating blueprint and construction sprites...\n\n");

        $basePath = Yii::getAlias('@app/..');
        $entities = EntityType::find()->asArray()->all();

        foreach ($entities as $entity) {
            $folder = $entity['image_url'];
            $widthTiles = $entity['width'];
            $heightTiles = $entity['height'];

            $this->generateSpritesForEntity($basePath, $folder, $widthTiles, $heightTiles);
        }

        $this->stdout("\nDone! Generated sprites for " . count($entities) . " entities.\n");
        $this->stdout("Run 'php yii entity/generate' to rebuild atlases.\n");
        $this->stdout("Then run 'npm run assets' to rebuild game assets.\n");

        return 0;
    }

    private function generateSpritesForEntity($basePath, $folder, $widthTiles, $heightTiles)
    {
        $entityPath = $basePath . '/public/assets/tiles/entities/' . $folder;

        if (!is_dir($entityPath)) {
            $this->stdout("Warning: Entity folder not found: {$folder}\n");
            return;
        }

        $normalPath = $entityPath . '/normal.png';
        if (!file_exists($normalPath)) {
            $this->stdout("Warning: normal.png not found for {$folder}\n");
            return;
        }

        $pixelWidth = $widthTiles * Yii::$app->params['tile_width'];
        $pixelHeight = $heightTiles * Yii::$app->params['tile_height'];

        // Load normal sprite
        $normal = imagecreatefrompng($normalPath);
        imagesavealpha($normal, true);

        // 1. Generate blueprint.png with checkerboard transparency
        $this->generateBlueprint($normal, $entityPath, $pixelWidth, $pixelHeight);

        // 2. Generate construction frames (10%, 20%, ... 90%)
        $this->generateConstructionFrames($normal, $entityPath, $pixelWidth, $pixelHeight);

        imagedestroy($normal);

        $this->stdout("Generated sprites for {$folder}\n");
    }

    /**
     * Generate blueprint.png with checkerboard transparency
     */
    private function generateBlueprint($normal, $entityPath, $width, $height)
    {
        $blueprint = imagecreatetruecolor($width, $height);
        imagesavealpha($blueprint, true);
        $transparent = imagecolorallocatealpha($blueprint, 0, 0, 0, 127);
        imagefill($blueprint, 0, 0, $transparent);

        // Copy pixels with checkerboard pattern
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                // Checkerboard: make every other pixel transparent
                if (($x + $y) % 2 === 0) {
                    $color = imagecolorat($normal, $x, $y);
                    imagesetpixel($blueprint, $x, $y, $color);
                }
                // else: leave transparent (already filled)
            }
        }

        imagepng($blueprint, $entityPath . '/blueprint.png');
        imagedestroy($blueprint);
    }

    /**
     * Generate construction frames (10% to 90%)
     */
    private function generateConstructionFrames($normal, $entityPath, $width, $height)
    {
        // Load blueprint
        $blueprintPath = $entityPath . '/blueprint.png';
        if (!file_exists($blueprintPath)) {
            return;
        }

        $blueprint = imagecreatefrompng($blueprintPath);
        imagesavealpha($blueprint, true);

        // Generate 9 frames: 10%, 20%, ..., 90%
        for ($percent = 10; $percent <= 90; $percent += 10) {
            $frame = imagecreatetruecolor($width, $height);
            imagesavealpha($frame, true);
            $transparent = imagecolorallocatealpha($frame, 0, 0, 0, 127);
            imagefill($frame, 0, 0, $transparent);

            // Calculate split line (from bottom)
            $splitY = (int)($height * (100 - $percent) / 100);

            // Top part: blueprint
            if ($splitY > 0) {
                imagecopy($frame, $blueprint, 0, 0, 0, 0, $width, $splitY);
            }

            // Bottom part: normal
            if ($splitY < $height) {
                imagecopy($frame, $normal, 0, $splitY, 0, $splitY, $width, $height - $splitY);
            }

            $framePath = $entityPath . '/construction_' . $percent . '.png';
            imagepng($frame, $framePath);
            imagedestroy($frame);
        }

        imagedestroy($blueprint);
    }
}
