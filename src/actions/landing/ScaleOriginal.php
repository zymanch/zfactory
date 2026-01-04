<?php

namespace actions\landing;

use actions\ConsoleAction;
use models\Landing;
use Yii;
use yii\helpers\Console;

/**
 * Scale original.png files to tile size
 * Usage: php yii landing/scale-original
 */
class ScaleOriginal extends ConsoleAction
{
    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
    }

    public function run()
    {
        $this->stdout("=== Scaling Landing Original Images ===\n\n");

        // Get all landings
        $landings = Landing::find()->all();

        if (empty($landings)) {
            $this->stdout("No landings to process.\n", Console::FG_YELLOW);
            return 0;
        }

        $this->stdout("Processing " . count($landings) . " landings...\n\n");

        $successCount = 0;
        $failCount = 0;

        foreach ($landings as $landing) {
            $landingBL = \bl\landing\LandingFactory::create($landing->landing_id);
            $generator = $landingBL->getGenerator();

            if (!$generator) {
                $this->stdout("Warning: No generator for '{$landing->folder}'\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Landing: {$landing->folder}\n");

            try {
                $success = $generator->scaleOriginals($landing);

                if ($success) {
                    $successCount++;
                } else {
                    $failCount++;
                    $this->stdout("  No originals found\n", Console::FG_YELLOW);
                }
            } catch (\Exception $e) {
                $failCount++;
                $this->stdout("  Error: " . $e->getMessage() . "\n", Console::FG_RED);
            }
        }

        // Summary
        $this->stdout("\n=== Scaling Complete ===\n");
        $this->stdout("Success: {$successCount}\n", Console::FG_GREEN);
        if ($failCount > 0) {
            $this->stdout("Skipped: {$failCount}\n", Console::FG_YELLOW);
        }
        $this->stdout("\nRun 'php yii landing/generate' to regenerate atlases.\n");

        return 0;
    }
}
