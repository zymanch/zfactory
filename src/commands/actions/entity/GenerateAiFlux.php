<?php

namespace commands\actions\entity;

use commands\actions\ConsoleAction;
use app\client\ComfyUIClient;
use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\types\AbstractEntityType;
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

    /** @var string */
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->fluxClient = new ComfyUIClient();
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
        // Note: Some generators (like ConveyorGenerator for dual/fast_dual) don't need ComfyUI
        $comfyUIAvailable = false;
        if (!$statesOnly) {
            $comfyUIAvailable = $this->fluxClient->isAvailable();
            if ($comfyUIAvailable) {
                $this->stdout("ComfyUI is running ✓\n\n", Console::FG_GREEN);
            } else {
                $this->stdout("Warning: ComfyUI is not running at {$this->fluxClient->getApiUrl()}\n", Console::FG_YELLOW);
                $this->stdout("Some entities may fail to generate if they require AI.\n\n");
            }
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
            $generator = $entity->getGenerator();

            if (!$generator) {
                $this->stdout("Warning: No generator for '{$entity->folder}'\n", Console::FG_YELLOW);
                continue;
            }

            $this->stdout("Entity: {$entity->folder} ({$entity->name})\n");

            try {
                $success = false;

                if ($statesOnly) {
                    // Regenerate states from existing normal.png
                    $success = $entity->generateAllStates();
                } else {
                    // Generate new sprite
                    $result = $generator->generate($entity, $testMode);

                    // Check if generator returns GD resource (new format) or bool (old format)
                    if (is_resource($result)) {
                        // New GD-based format
                        $gdImage = $result;

                        // Save normal sprite
                        $success = $entity->saveNormalSprite($gdImage);

                        if ($success && !$testMode) {
                            // Generate all state variants
                            $entity->generateAllStates();
                        }

                        // Free GD resource
                        imagedestroy($gdImage);
                    } else {
                        // Old bool-based format (backward compatibility)
                        $success = $result;
                    }

                    // Track rotational entities for variant generation (old format only)
                    if ($success && $generator->isRotational() && !is_resource($result)) {
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
                /** @var EntityType $entity */
                $entity = $item['entity'];
                /** @var AbstractEntityGenerator $generator */
                $generator = $item['generator'];

                $this->stdout("Rotating: {$entity->folder}\n");
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
     * @return AbstractEntityType[]
     */
    private function getEntitiesToProcess($entityName)
    {
        if ($entityName === 'all') {
            // Get all entities that have generators
            $allEntities = EntityType::find()->all();
            $result = [];

            foreach ($allEntities as $entity) {
                if ($entity->getGenerator()) {
                    $result[] = $entity;
                }
            }

            return $result;
        } elseif (strpos($entityName, '%') !== false) {
            // Pattern matching (e.g., "conveyor" matches "conveyor%")
            $pattern = $entityName;
            if (strpos($pattern, '%') === false) {
                $pattern .= '%';
            }

            $entities = EntityType::find()
                ->where(['like', 'folder', $pattern, false])
                ->all();

            $result = [];
            foreach ($entities as $entity) {
                if ($entity->getGenerator()) {
                    $result[] = $entity;
                }
            }

            return $result;
        } else {
            // Get specific entity
            $entity = EntityType::find()
                ->where(['folder' => $entityName])
                ->one();

            if (!$entity) {
                $this->stdout("Error: Entity '{$entityName}' not found.\n", Console::FG_RED);
                return [];
            }

            if (!$entity->getGenerator()) {
                $this->stdout("Error: No generator for entity '{$entityName}'.\n", Console::FG_RED);
                return [];
            }

            return [$entity];
        }
    }
}
