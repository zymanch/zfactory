<?php

namespace actions\landing;

use models\Landing;
use Yii;

/**
 * Generate multiple variations with different seeds for selection
 * Creates a collection of 20 different generations per landing type
 *
 * Usage: php yii landing/generate-sids [landing_name] [count]
 * Examples:
 *   php yii landing/generate-sids grass      # Generate 20 versions of grass
 *   php yii landing/generate-sids all        # Generate for all landings
 *   php yii landing/generate-sids grass 50   # Generate 50 versions
 */
class GenerateSids extends GenerateAi
{
    public function run($landingName = 'all', $count = 20)
    {
        $this->stdout("Generating seed collection for landing sprites...\n\n");

        $apiUrl = 'http://localhost:7860';
        $basePath = Yii::getAlias('@app/..');
        $landingDir = $basePath . '/public/assets/tiles/landing';

        // Get prompts from parent class
        $prompts = $this->getPrompts();

        // Get landings to process (excluding sky)
        $landingsToProcess = [];
        if ($landingName === 'all') {
            $landings = Landing::find()->asArray()->all();
            foreach ($landings as $landing) {
                $name = str_replace('.png', '', $landing['image_url']);
                // Skip sky
                if ($name === 'sky') {
                    continue;
                }
                if (isset($prompts[$name])) {
                    $landingsToProcess[$name] = $landing;
                }
            }
        } else {
            // Skip sky
            if ($landingName === 'sky') {
                $this->stdout("Error: Cannot generate sids for sky landing.\n");
                return 1;
            }

            $landing = Landing::find()->where(['image_url' => $landingName . '.png'])->asArray()->one();
            if ($landing && isset($prompts[$landingName])) {
                $landingsToProcess[$landingName] = $landing;
            } else {
                $this->stdout("Error: Landing '{$landingName}' not found or no prompt defined.\n");
                return 1;
            }
        }

        if (empty($landingsToProcess)) {
            $this->stdout("No landings to process.\n");
            return 1;
        }

        foreach ($landingsToProcess as $name => $landing) {
            $this->stdout("Generating {$count} seed variations for {$name}...\n");

            $landingPath = $landingDir . '/' . $name;
            $sidsPath = $landingPath . '/sids';

            // Create sids directory
            if (!is_dir($sidsPath)) {
                mkdir($sidsPath, 0755, true);
            }

            // Generate multiple versions with different seeds
            for ($i = 0; $i < $count; $i++) {
                $this->stdout("  Generating variation " . ($i + 1) . "/{$count}...\n");

                // Generate image via txt2img
                $imageData = $this->generateViaSdApi(
                    $apiUrl,
                    $prompts[$name]['positive'],
                    $prompts[$name]['negative'],
                    512,
                    384
                );

                if (!$imageData) {
                    $this->stdout("    Error: Failed to generate image\n");
                    continue;
                }

                $imageBase64 = $imageData['image'];
                $seed = $imageData['seed'];

                // Save with seed as filename
                $filename = $sidsPath . '/' . $seed . '.png';
                file_put_contents($filename, base64_decode($imageBase64));
                $this->stdout("    Saved: {$seed}.png\n");

                // Apply transparency for island_edge
                if ($name === 'island_edge') {
                    $this->makeBottomTransparent($filename, 0.5);
                }
            }

            $this->stdout("  Completed {$name}: {$count} variations saved in sids/\n\n");
        }

        $this->stdout("\nDone! Generated seed collections.\n");
        $this->stdout("Browse the sids/ folders and copy your favorite to use as base.\n");

        return 0;
    }
}
