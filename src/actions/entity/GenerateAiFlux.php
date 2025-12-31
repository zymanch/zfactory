<?php

namespace actions\entity;

use actions\ConsoleAction;
use app\client\ComfyUIClient;
use bl\entity\generators\EntityGeneratorFactory;
use models\EntityType;
use Yii;
use yii\helpers\Console;

/**
 * Generate entity sprites using FLUX.1 Dev via ComfyUI
 *
 * Usage: php yii entity/generate-ai-flux [entity_name] [testMode] [statesOnly]
 *
 * Examples:
 *   php yii entity/generate-ai-flux tree_pine 1          (test mode, only normal.png)
 *   php yii entity/generate-ai-flux tree_pine            (full generation, all 5 states)
 *   php yii entity/generate-ai-flux conveyor 0 1         (regenerate only states, skip normal.png)
 *   php yii entity/generate-ai-flux all                  (generate all entities)
 */
class GenerateAiFlux extends ConsoleAction
{
    /** @var ComfyUIClient */
    private $fluxClient;

    /** @var EntityGeneratorFactory */
    private $factory;

    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->fluxClient = new ComfyUIClient();
        $this->factory = new EntityGeneratorFactory($this->fluxClient, $this->basePath);
    }

    public function run($entityName = 'all', $testMode = false, $statesOnly = false)
    {
        $this->stdout("=== Entity Sprite Generator (FLUX.1 Dev via ComfyUI) ===\n\n");

        if ($statesOnly) {
            $this->stdout("STATES ONLY MODE: Regenerating damaged/blueprint/selected from existing normal.png\n\n", Console::FG_CYAN);
        } elseif ($testMode) {
            $this->stdout("TEST MODE: Generating only normal.png\n\n", Console::FG_YELLOW);
        } else {
            $this->stdout("FULL MODE: Generating all 5 sprite states\n\n", Console::FG_GREEN);
        }

        // Check if ComfyUI is running (skip for statesOnly mode)
        if (!$statesOnly && !$this->fluxClient->isAvailable()) {
            $this->stdout("Error: ComfyUI is not running at {$this->fluxClient->getApiUrl()}\n", Console::FG_RED);
            $this->stdout("Please start ComfyUI first.\n");
            return 1;
        }

        if (!$statesOnly) {
            $this->stdout("ComfyUI is running ✓\n\n", Console::FG_GREEN);
        }

        // Get entities to process
        $entitiesToProcess = $this->getEntitiesToProcess($entityName);

        if (empty($entitiesToProcess)) {
            $this->stdout("No entities to process.\n", Console::FG_YELLOW);
            return 1;
        }

        $this->stdout("Processing " . count($entitiesToProcess) . " entities...\n\n");

        // Process entities
        $successCount = 0;
        $failCount = 0;
        $rotationalEntities = [];

        foreach ($entitiesToProcess as $entity) {
            $generator = $this->factory->getGenerator($entity);

            if (!$generator) {
                $this->stdout("Warning: No generator for '{$entity->image_url}'\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Entity: {$entity->image_url} ({$entity->name})\n");

            try {
                if ($statesOnly) {
                    $success = $generator->generateStates($entity);
                } else {
                    $success = $generator->generate($entity, $testMode);

                    // Track rotational entities for variant generation
                    if ($success && $generator->isRotational()) {
                        $rotationalEntities[] = ['entity' => $entity, 'generator' => $generator];
                    }
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

        // Generate rotational variants
        if (!$statesOnly && !empty($rotationalEntities)) {
            $this->stdout("--- Generating rotational variants ---\n\n");

            foreach ($rotationalEntities as $item) {
                $entity = $item['entity'];
                $generator = $item['generator'];

                $this->stdout("Rotating: {$entity->image_url}\n");
                $generator->generateRotationalVariants($entity);
            }
        }

        // Summary
        $this->stdout("\n=== Generation Complete ===\n");
        $this->stdout("Success: {$successCount}\n", Console::FG_GREEN);
        if ($failCount > 0) {
            $this->stdout("Failed: {$failCount}\n", Console::FG_RED);
        }
        $this->stdout("\nRun 'php yii entity/generate' to create texture atlases.\n");

        return 0;
    }

    /**
     * Get entities to process based on entity name parameter
     * @param string $entityName
     * @return EntityType[]
     */
    private function getEntitiesToProcess($entityName)
    {
        $registeredUrls = $this->factory->getRegisteredImageUrls();

        if ($entityName === 'all') {
            // Get all entities that have generators
            return EntityType::find()
                ->where(['in', 'image_url', $registeredUrls])
                ->all();
        } else {
            // Get specific entity
            $entity = EntityType::find()
                ->where(['image_url' => $entityName])
                ->one();

            if (!$entity) {
                $this->stdout("Error: Entity '{$entityName}' not found.\n", Console::FG_RED);
                return [];
            }

            if (!$this->factory->hasGenerator($entityName)) {
                $this->stdout("Error: No generator for entity '{$entityName}'.\n", Console::FG_RED);
                return [];
            }

            return [$entity];
        }
    }
}
