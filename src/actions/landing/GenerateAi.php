<?php

namespace actions\landing;

use actions\ConsoleAction;
use app\client\StableDiffusionClient;
use bl\landing\generators\LandingGeneratorFactory;
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

    /** @var LandingGeneratorFactory */
    private $factory;

    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->sdClient = new StableDiffusionClient();
        $this->factory = new LandingGeneratorFactory(null, $this->sdClient, $this->basePath);
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
            $generator = $this->factory->getGenerator($landing);

            if (!$generator) {
                $this->stdout("Warning: No generator for '{$landing->folder}'\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Landing: {$landing->folder} ({$landing->name})\n");

            try {
                $success = $generator->generateVariationsWithStableDiffusion($landing);

                if ($success) {
                    $successCount++;
                    $this->stdout("  Success\n", Console::FG_GREEN);
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
        $registeredFolders = $this->factory->getRegisteredFolders();

        if ($landingName === 'all') {
            // Get all landings that have generators
            return Landing::find()
                ->where(['in', 'folder', $registeredFolders])
                ->all();
        } else {
            // Get specific landing
            $landing = Landing::find()
                ->where(['folder' => $landingName])
                ->one();

            if (!$landing) {
                $this->stdout("Error: Landing '{$landingName}' not found.\n", Console::FG_RED);
                return [];
            }

            if (!$this->factory->hasGenerator($landingName)) {
                $this->stdout("Error: No generator for landing '{$landingName}'.\n", Console::FG_RED);
                return [];
            }

            return [$landing];
        }
    }
}
