<?php

namespace actions\landing;

use actions\ConsoleAction;
use models\Landing;
use Yii;
use yii\helpers\Console;

/**
 * Scale original.png files to tile size and create variations
 * Usage: php yii landing/scale-original
 */
class ScaleOriginal extends ConsoleAction
{
    public function run()
    {
        $this->stdout("Scaling original images and creating variations...\n\n");

        $basePath = Yii::getAlias('@app/..');
        $landingDir = $basePath . '/public/assets/tiles/landing';
        $tileWidth = Yii::$app->params['tile_width'];
        $tileHeight = Yii::$app->params['tile_height'];

        // Get all landings from database
        $landings = Landing::find()->asArray()->all();
        $processedCount = 0;

        foreach ($landings as $landing) {
            $landingName = $landing['folder'];
            $landingPath = realpath($landingDir . '/' . $landingName);
            $variationsCount = $landing['variations_count'] ?? 5;

            // Check if landing folder exists
            if (!is_dir($landingPath)) {
                $this->stdout("  Skipping {$landingName}: folder not found\n");
                continue;
            }

            $originalFile = $landingPath . '/' . $landingName . '_0_original.png';

            if (!$originalFile) {
                continue; // No original file found, skip silently
            }

            $this->stdout("Processing {$landingName}...\n");

            // Process each variation separately
            $scaledCount = 0;
            for ($i = 0; $i < $variationsCount; $i++) {
                $varOriginalFile = $landingPath . '/' . $landingName . '_' . $i . '_original.png';

                // If specific variation file doesn't exist, try base original
                if (!file_exists($varOriginalFile)) {
                    $varOriginalFile = $originalFile;
                }

                if (!file_exists($varOriginalFile)) {
                    continue;
                }

                // Load variation image
                $originalImage = $this->loadImageAny($varOriginalFile);
                if (!$originalImage) {
                    $this->stdout("  Warning: Could not load variation {$i}\n");
                    continue;
                }

                // Scale to 32x24 using nearest neighbor
                $scaledImage = imagecreatetruecolor($tileWidth, $tileHeight);
                imagealphablending($scaledImage, false);
                imagesavealpha($scaledImage, true);
                imagesetinterpolation($scaledImage, IMG_NEAREST_NEIGHBOUR);

                imagecopyresampled(
                    $scaledImage,
                    $originalImage,
                    0, 0, 0, 0,
                    $tileWidth, $tileHeight,
                    imagesx($originalImage), imagesy($originalImage)
                );

                // Save scaled variation
                $variationPath = $landingPath . '/' . $landingName . '_' . $i . '.png';
                imagepng($scaledImage, $variationPath, 9);

                imagedestroy($originalImage);
                imagedestroy($scaledImage);
                $scaledCount++;
            }

            if ($scaledCount > 0) {
                $this->stdout("  Scaled {$scaledCount} variations\n");
                $processedCount++;
            }
        }

        if ($processedCount === 0) {
            $this->stdout("No original files found. Place 'original.png' in landing folders.\n");
        } else {
            $this->stdout("\nDone! Processed {$processedCount} landings.\n");
            $this->stdout("Run 'php yii landing/generate' to regenerate atlases.\n");
        }

        return 0;
    }

    /**
     * Load image from any format
     */
    private function loadImageAny($path)
    {
        // Определяем тип изображения
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
                $this->stdout("  Unsupported image format: {$mimeType}\n", Console::FG_RED);
                return null;
        }

        if ($image) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }
}
