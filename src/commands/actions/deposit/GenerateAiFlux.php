<?php

namespace commands\actions\deposit;

use commands\actions\ConsoleAction;
use app\client\ComfyUIClient;
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

    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->fluxClient = new ComfyUIClient();
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

            // Get generator from deposit instance
            $generator = $deposit->getGenerator();

            if (!$generator) {
                $this->stdout("Warning: No generator for '{$deposit->image_url}'\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Deposit: {$deposit->image_url} ({$deposit->name})\n");

            try {
                $result = $generator->generate($deposit);
                $success = false;

                // Check if generator returns GD resource (new format) or bool (old format)
                if (is_resource($result)) {
                    // New GD-based format
                    $gdImage = $result;

                    // Save normal sprite
                    $success = $deposit->saveNormalSprite($gdImage);

                    if ($success) {
                        // Generate all state variants
                        $deposit->generateAllStates();
                    }

                    // Free GD resource
                    imagedestroy($gdImage);
                } else {
                    // Old bool-based format (backward compatibility)
                    $success = $result;
                }

                if ($success) {
                    $successCount++;
                    $this->stdout("  ✓ Success\n", Console::FG_GREEN);
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
        // Deposits with generators (hardcoded list matching instantiate() method)
        $registeredUrls = [
            'ore_iron',
            'ore_copper',
            'ore_aluminum',
            'ore_titanium',
            'ore_silver',
            'ore_gold',
        ];

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

            if (!in_array($depositName, $registeredUrls)) {
                $this->stdout("Error: No generator for deposit '{$depositName}'.\n", Console::FG_RED);
                return [];
            }

            return [$deposit];
        }
    }
}
