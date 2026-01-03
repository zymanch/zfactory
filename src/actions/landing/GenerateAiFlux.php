<?php

namespace actions\landing;

use actions\ConsoleAction;
use app\client\ComfyUIClient;
use bl\landing\generators\LandingGeneratorFactory;
use models\Landing;
use Yii;
use yii\helpers\Console;

/**
 * Generate landing sprites using FLUX.1 Dev via ComfyUI
 * Usage: php yii landing/generate-ai-flux [landing_name] [testMode]
 * Examples:
 *   php yii landing/generate-ai-flux grass --testMode=1  (quick test, only base sprite)
 *   php yii landing/generate-ai-flux grass              (full generation with 4 variations)
 *   php yii landing/generate-ai-flux all
 */
class GenerateAiFlux extends ConsoleAction
{
    public $landingName = 'all';
    public $testMode = false;

    /** @var ComfyUIClient */
    private $fluxClient;

    /** @var LandingGeneratorFactory */
    private $factory;

    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->fluxClient = new ComfyUIClient();
        $this->factory = new LandingGeneratorFactory($this->fluxClient, null, $this->basePath);
    }

    public function run($landingName = 'all', $testMode = false)
    {
        $this->landingName = $landingName;
        $this->testMode = $testMode;

        $this->stdout("=== Landing Sprite Generator (FLUX.1 Dev via ComfyUI) ===\n\n");

        if ($testMode) {
            $this->stdout("TEST MODE: Generating only base sprite (no variations)\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout("FULL MODE: Generating base sprite + 4 variations\n\n", Console::FG_GREEN);
        }

        // Check if ComfyUI is running
        if (!$this->fluxClient->isAvailable()) {
            $this->stdout("Error: ComfyUI is not running at {$this->fluxClient->getApiUrl()}\n", Console::FG_RED);
            $this->stdout("Please start ComfyUI first: cd ai && start_comfyui.bat\n");
            return 1;
        }

        $this->stdout("ComfyUI is running\n\n", Console::FG_GREEN);

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
                $success = $generator->generateWithFlux($landing, $this->testMode);

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
