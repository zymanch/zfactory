<?php

namespace actions\deposit;

use actions\ConsoleAction;
use app\client\ComfyUIClient;
use bl\entity\generators\DepositGeneratorFactory;
use models\DepositType;
use Yii;
use yii\helpers\Console;

/**
 * Generate deposit sprites using FLUX.1 Dev via ComfyUI
 *
 * Usage: php yii deposit/generate-ai-flux [deposit_name|all|ores]
 *
 * Examples:
 *   php yii deposit/generate-ai-flux ore_iron      (single deposit)
 *   php yii deposit/generate-ai-flux ores          (all ores only)
 *   php yii deposit/generate-ai-flux all           (all deposits with generators)
 */
class GenerateAiFlux extends ConsoleAction
{
    /** @var ComfyUIClient */
    private $fluxClient;

    /** @var DepositGeneratorFactory */
    private $factory;

    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->fluxClient = new ComfyUIClient();
        $this->factory = new DepositGeneratorFactory($this->fluxClient, $this->basePath);
    }

    public function run($depositName = 'all')
    {
        $this->stdout("=== Deposit Sprite Generator (FLUX.1 Dev via ComfyUI) ===\n\n");

        // Check if ComfyUI is running
        if (!$this->fluxClient->isAvailable()) {
            $this->stdout("Error: ComfyUI is not running at {$this->fluxClient->getApiUrl()}\n", Console::FG_RED);
            $this->stdout("Please start ComfyUI first: cd ai && start_comfyui.bat\n");
            return 1;
        }

        $this->stdout("ComfyUI is running ✓\n\n", Console::FG_GREEN);

        // Get deposits to process
        $depositsToProcess = $this->getDepositsToProcess($depositName);

        if (empty($depositsToProcess)) {
            $this->stdout("No deposits to process.\n", Console::FG_YELLOW);
            return 1;
        }

        $this->stdout("Processing " . count($depositsToProcess) . " deposits...\n\n");

        // Process each deposit
        $successCount = 0;
        $failCount = 0;

        foreach ($depositsToProcess as $deposit) {
            $generator = $this->factory->getGenerator($deposit);

            if (!$generator) {
                $this->stdout("Warning: No generator for '{$deposit->image_url}'\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Deposit: {$deposit->image_url} ({$deposit->name})\n");

            try {
                $success = $generator->generate($deposit);

                if ($success) {
                    $successCount++;
                } else {
                    $failCount++;
                    $this->stdout("  ✗ Failed\n", Console::FG_RED);
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->stdout("  ✗ Error: " . $e->getMessage() . "\n", Console::FG_RED);
            }

            $this->stdout("\n");
        }

        // Summary
        $this->stdout("=== Generation Complete ===\n");
        $this->stdout("Success: {$successCount}\n", Console::FG_GREEN);
        if ($failCount > 0) {
            $this->stdout("Failed: {$failCount}\n", Console::FG_RED);
        }

        return 0;
    }

    /**
     * Get deposits to process based on deposit name parameter
     * @param string $depositName
     * @return DepositType[]
     */
    private function getDepositsToProcess($depositName)
    {
        $registeredUrls = $this->factory->getRegisteredImageUrls();

        if ($depositName === 'all') {
            // All deposits that have generators
            return DepositType::find()
                ->where(['in', 'image_url', $registeredUrls])
                ->all();
        } elseif ($depositName === 'ores') {
            // Only ore deposits
            return DepositType::find()
                ->where(['type' => 'ore'])
                ->andWhere(['in', 'image_url', $registeredUrls])
                ->all();
        } else {
            // Specific deposit
            $deposit = DepositType::find()
                ->where(['image_url' => $depositName])
                ->one();

            if (!$deposit) {
                $this->stdout("Error: Deposit '{$depositName}' not found.\n", Console::FG_RED);
                return [];
            }

            if (!$this->factory->hasGenerator($depositName)) {
                $this->stdout("Error: No generator for deposit '{$depositName}'.\n", Console::FG_RED);
                return [];
            }

            return [$deposit];
        }
    }
}
