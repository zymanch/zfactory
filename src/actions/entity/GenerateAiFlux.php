<?php

namespace actions\entity;

use actions\ConsoleAction;
use generators\base\FluxAiGenerator;
use generators\BuildingGenerator;
use generators\ConveyorGenerator;
use generators\EyeGenerator;
use generators\ManipulatorGenerator;
use generators\ReliefGenerator;
use generators\ResourceGenerator;
use generators\TreeGenerator;
use models\EntityType;
use Yii;
use yii\helpers\Console;

/**
 * Generate entity sprites using FLUX.1 Dev via ComfyUI (Refactored Orchestrator)
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
    private $generators = [];
    private $fluxAi;
    private $basePath;

    public function init()
    {
        parent::init();

        $this->basePath = Yii::getAlias('@app/..');
        $this->fluxAi = new FluxAiGenerator();

        // Initialize category generators
        $this->generators = [
            'building' => new BuildingGenerator($this->fluxAi, $this->basePath),
            'mining' => new BuildingGenerator($this->fluxAi, $this->basePath), // drills use building generator
            'special' => new BuildingGenerator($this->fluxAi, $this->basePath), // headquarters, special buildings
            'tree' => new TreeGenerator($this->fluxAi, $this->basePath),
            'transporter' => new ConveyorGenerator($this->fluxAi, $this->basePath),
            'manipulator' => new ManipulatorGenerator($this->fluxAi, $this->basePath),
            'relief' => new ReliefGenerator($this->fluxAi, $this->basePath),
            'resource' => new ResourceGenerator($this->fluxAi, $this->basePath),
            'eye' => new EyeGenerator($this->fluxAi, $this->basePath),
        ];
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
        if (!$statesOnly && !$this->fluxAi->checkRunning()) {
            $this->stdout("Error: ComfyUI is not running at http://localhost:8188\n", Console::FG_RED);
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

        // Group entities by category for batch processing
        $entitiesByCategory = [];
        foreach ($entitiesToProcess as $entity) {
            $type = $entity->type;
            if (!isset($entitiesByCategory[$type])) {
                $entitiesByCategory[$type] = [];
            }
            $entitiesByCategory[$type][] = $entity;
        }

        // Process each category
        $successCount = 0;
        $failCount = 0;

        foreach ($entitiesByCategory as $type => $entities) {
            if (!isset($this->generators[$type])) {
                $this->stdout("Warning: No generator for type '{$type}'\n", Console::FG_YELLOW);
                continue;
            }

            $generator = $this->generators[$type];
            $this->stdout("--- Processing category: {$type} (" . count($entities) . " entities) ---\n");

            foreach ($entities as $entity) {
                $this->stdout("\nEntity: {$entity->image_url} ({$entity->name})\n");

                try {
                    if ($statesOnly) {
                        // Только генерация состояний (damaged, blueprint, selected)
                        $success = $generator->generateStates($entity);
                    } else {
                        // Полная генерация или тестовый режим
                        $success = $generator->generate($entity, $testMode);
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
            }

            $this->stdout("\n");
        }

        // Generate rotational variants for conveyors and manipulators (skip in statesOnly if not needed)
        if (!$statesOnly) {
            $this->stdout("\n--- Generating rotational variants ---\n");

            if (isset($entitiesByCategory['transporter'])) {
                $this->stdout("\nConveyors:\n");
                $conveyorEntities = [];
                foreach ($entitiesByCategory['transporter'] as $entity) {
                    $conveyorEntities[$entity->image_url] = $entity;
                }
                /** @var ConveyorGenerator $conveyorGen */
                $conveyorGen = $this->generators['transporter'];
                $conveyorGen->generateRotationalVariants($conveyorEntities);
            }

            if (isset($entitiesByCategory['manipulator'])) {
                $this->stdout("\nManipulators:\n");
                $manipulatorEntities = [];
                foreach ($entitiesByCategory['manipulator'] as $entity) {
                    $manipulatorEntities[$entity->image_url] = $entity;
                }
                /** @var ManipulatorGenerator $manipulatorGen */
                $manipulatorGen = $this->generators['manipulator'];
                $manipulatorGen->generateRotationalVariants($manipulatorEntities);
            }
        } else {
            // В режиме statesOnly - генерируем states для ротированных вариантов
            $this->stdout("\n--- Generating states for rotational variants ---\n");

            if (isset($entitiesByCategory['transporter'])) {
                $this->stdout("\nConveyors:\n");
                foreach (['conveyor_up', 'conveyor_down', 'conveyor_left'] as $variantName) {
                    $variantEntity = \models\EntityType::find()->where(['image_url' => $variantName])->one();
                    if ($variantEntity) {
                        $this->stdout("  {$variantName}...\n");
                        $this->generators['transporter']->generateStates($variantEntity);
                    }
                }
            }

            if (isset($entitiesByCategory['manipulator'])) {
                $this->stdout("\nManipulators:\n");
                $baseNames = ['manipulator_short', 'manipulator_long'];
                foreach ($baseNames as $baseName) {
                    foreach (['_up', '_down', '_left'] as $suffix) {
                        $variantName = $baseName . $suffix;
                        $variantEntity = \models\EntityType::find()->where(['image_url' => $variantName])->one();
                        if ($variantEntity) {
                            $this->stdout("  {$variantName}...\n");
                            $this->generators['manipulator']->generateStates($variantEntity);
                        }
                    }
                }
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
        if ($entityName === 'all') {
            // Get all entities that have generators
            return EntityType::find()
                ->where(['in', 'type', array_keys($this->generators)])
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

            if (!isset($this->generators[$entity->type])) {
                $this->stdout("Error: No generator for entity type '{$entity->type}'.\n", Console::FG_RED);
                return [];
            }

            return [$entity];
        }
    }
}
