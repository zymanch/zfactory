<?php

namespace actions\landing;

use actions\ConsoleAction;
use app\client\StableDiffusionClient;
use models\Landing;
use Yii;
use yii\helpers\Console;

/**
 * Generate landing sprite variations using Stable Diffusion img2img
 * Usage: php yii landing/generate-ai [landing_name]
 * Examples:
 *   php yii landing/generate-ai grass
 *   php yii landing/generate-ai all
 *
 * Note: Base images must exist (generated via FLUX first)
 */
class GenerateAi extends ConsoleAction
{
    public $landingName = 'all';

    /** @var StableDiffusionClient */
    private $sdClient;

    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->sdClient = new StableDiffusionClient();
    }

    public function run($landingName = 'all')
    {
        $this->landingName = $landingName;

        $this->stdout("=== Landing Sprite Variation Generator (Stable Diffusion img2img) ===\n\n");

        // Check if SD is running
        if (!$this->sdClient->isAvailable()) {
            $this->stdout("Error: Stable Diffusion WebUI is not running at {$this->sdClient->getApiUrl()}\n", Console::FG_RED);
            $this->stdout("Please start it first.\n");
            return 1;
        }

        $this->stdout("Stable Diffusion is running\n\n", Console::FG_GREEN);

        // Get landings to process
        $landingsToProcess = $this->getLandingsToProcess($landingName);

        if (empty($landingsToProcess)) {
            $this->stdout("No landings to process.\n", Console::FG_YELLOW);
            return 1;
        }

        $this->stdout("Processing " . count($landingsToProcess) . " landings...\n\n");

        // Process landings
        $successCount = 0;
        $failCount = 0;

        foreach ($landingsToProcess as $landing) {
            $landingBL = \bl\landing\LandingFactory::create($landing->landing_id);
            $generator = $landingBL->getGenerator();

            if (!$generator) {
                $this->stdout("Warning: No generator for '{$landing->folder}'\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Landing: {$landing->folder} ({$landing->name})\n");

            try {
                $variationPrompts = $generator->getVariationPrompts();
                $variationsCount = min($generator->getVariationsCount() - 1, count($variationPrompts));

                $this->stdout("  Generating {$variationsCount} variations with Stable Diffusion...\n");

                $generatedCount = 0;
                for ($i = 0; $i < $variationsCount; $i++) {
                    $variationNumber = $i + 1;
                    $gd = $generator->generateSpriteVariation($variationNumber);

                    if (!$gd) {
                        $this->stdout("    Warning: Failed to generate variation {$variationNumber}\n", Console::FG_YELLOW);
                        continue;
                    }

                    $saved = $generator->saveSpriteVariation($gd, $variationNumber);
                    imagedestroy($gd);

                    if ($saved) {
                        $generatedCount++;
                        $this->stdout("    Saved variation {$variationNumber}\n");
                    } else {
                        $this->stdout("    Warning: Failed to save variation {$variationNumber}\n", Console::FG_YELLOW);
                    }
                }

                if ($generatedCount > 0) {
                    $successCount++;
                    $this->stdout("  Success ({$generatedCount}/{$variationsCount} variations)\n", Console::FG_GREEN);
                } else {
                    $failCount++;
                    $this->stdout("  Failed\n", Console::FG_RED);
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->stdout("  Error: " . $e->getMessage() . "\n", Console::FG_RED);
            }

            $this->stdout("\n");
        }

        // Summary
        $this->stdout("\n=== Generation Complete ===\n");
        $this->stdout("Success: {$successCount}\n", Console::FG_GREEN);
        if ($failCount > 0) {
            $this->stdout("Failed: {$failCount}\n", Console::FG_RED);
        }

        $this->stdout("\nRunning scale-original...\n\n");

        // Automatically run scale-original
        $result = $this->controller->runAction('scale-original');

        if ($result === 0) {
            $this->stdout("\nDone! Now run:\n");
            $this->stdout("  php yii landing/generate\n");
            $this->stdout("  npm run assets\n");
        }

        return $result;
    }

    /**
     * Get landings to process based on landing name parameter
     * @param string $landingName
     * @return Landing[]
     */
    private function getLandingsToProcess(string $landingName): array
    {
        if ($landingName === 'all') {
            // Get all landings
            return Landing::find()->all();
        } else {
            // Get specific landing
            $landing = Landing::find()
                ->where(['folder' => $landingName])
                ->one();

            if (!$landing) {
                $this->stdout("Error: Landing '{$landingName}' not found.\n", Console::FG_RED);
                return [];
            }

            return [$landing];
        }
    }
}
