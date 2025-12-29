<?php

namespace actions\deposit;

use actions\ConsoleAction;
use generators\base\FluxAiGenerator;
use generators\DepositGenerator;
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
 *   php yii deposit/generate-ai-flux all           (all deposits with prompts)
 */
class GenerateAiFlux extends ConsoleAction
{
    private $fluxAi;
    private $generator;
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->fluxAi = new FluxAiGenerator();
        $this->generator = new DepositGenerator($this->fluxAi, $this->basePath);
    }

    public function run($depositName = 'all')
    {
        $this->stdout("=== Deposit Sprite Generator (FLUX.1 Dev via ComfyUI) ===\n\n");

        // Check if ComfyUI is running
        if (!$this->fluxAi->checkRunning()) {
            $this->stdout("Error: ComfyUI is not running at http://localhost:8188\n", Console::FG_RED);
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
            $this->stdout("Deposit: {$deposit->image_url} ({$deposit->name})\n");

            try {
                $success = $this->generator->generate($deposit);

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
        $prompts = $this->generator->getPrompts();

        if ($depositName === 'all') {
            // All deposits that have prompts
            $allDeposits = DepositType::find()->all();
            $result = [];
            foreach ($allDeposits as $deposit) {
                if (isset($prompts[$deposit->image_url])) {
                    $result[] = $deposit;
                }
            }
            return $result;
        } elseif ($depositName === 'ores') {
            // Only ore deposits
            $oreDeposits = DepositType::find()->where(['type' => 'ore'])->all();
            $result = [];
            foreach ($oreDeposits as $deposit) {
                if (isset($prompts[$deposit->image_url])) {
                    $result[] = $deposit;
                }
            }
            return $result;
        } else {
            // Specific deposit
            $deposit = DepositType::find()
                ->where(['image_url' => $depositName])
                ->one();

            if (!$deposit) {
                $this->stdout("Error: Deposit '{$depositName}' not found.\n", Console::FG_RED);
                return [];
            }

            if (!isset($prompts[$deposit->image_url])) {
                $this->stdout("Error: No prompt defined for deposit '{$depositName}'.\n", Console::FG_RED);
                return [];
            }

            return [$deposit];
        }
    }
}
