<?php

namespace commands;

use bl\entity\atlas_providers\AtlasProviderRegistry;
use models\EntityType;
use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Atlas generation commands
 */
class AtlasController extends Controller
{
    /**
     * Generate atlases for all entity types
     * Usage: php yii atlas/generate-all
     */
    public function actionGenerateAll()
    {
        $this->stdout("=== Generating Entity Atlases ===\n\n", Console::FG_CYAN);

        // Initialize provider registry
        AtlasProviderRegistry::init();

        $basePath = Yii::getAlias('@app/..');
        $entityTypes = EntityType::find()->all();

        $totalAtlases = 0;
        $successCount = 0;
        $errorCount = 0;

        foreach ($entityTypes as $entity) {
            try {
                $this->stdout("Processing #{$entity->entity_type_id} {$entity->name} (type={$entity->type}, subtype={$entity->subtype})...\n");

                // Check if provider exists
                if (!AtlasProviderRegistry::hasProvider($entity)) {
                    $this->stdout("  Skipped: No provider for type={$entity->type}, subtype={$entity->subtype}\n", Console::FG_YELLOW);
                    continue;
                }

                // Get provider
                $provider = AtlasProviderRegistry::getProvider($entity);

                // Get atlas generators
                $generators = $provider->getAtlasGenerators($entity);

                if (empty($generators)) {
                    $this->stdout("  No atlases to generate\n", Console::FG_YELLOW);
                    continue;
                }

                $entityPath = $basePath . '/public/assets/tiles/entities/' . $this->getEntityFolder($entity);

                if (!is_dir($entityPath)) {
                    mkdir($entityPath, 0755, true);
                }

                // Generate each atlas
                foreach ($generators as $atlasName => $generator) {
                    $atlasGd = $generator->generate();
                    $atlasPath = $entityPath . '/' . $atlasName . '.png';

                    imagepng($atlasGd, $atlasPath, 9);
                    imagedestroy($atlasGd);

                    $dims = $generator->getDimensions();
                    $this->stdout("  ✓ Generated {$atlasName}.png ({$dims['width']}×{$dims['height']})\n", Console::FG_GREEN);

                    $totalAtlases++;
                }

                $successCount++;

            } catch (\Exception $e) {
                $this->stdout("  Error: {$e->getMessage()}\n", Console::FG_RED);
                $this->stdout("  Stack trace:\n{$e->getTraceAsString()}\n", Console::FG_RED);
                $errorCount++;
            }
        }

        $this->stdout("\n=== Atlas Generation Complete ===\n", Console::FG_CYAN);
        $this->stdout("  Total atlases: {$totalAtlases}\n");
        $this->stdout("  Success: {$successCount} entities\n", Console::FG_GREEN);
        if ($errorCount > 0) {
            $this->stdout("  Errors: {$errorCount} entities\n", Console::FG_RED);
        }

        return 0;
    }

    /**
     * Generate atlases for specific entity type
     * Usage: php yii atlas/generate --entity_type_id=100
     */
    public function actionGenerate($entity_type_id = null)
    {
        if ($entity_type_id === null) {
            $this->stdout("Error: --entity_type_id required\n", Console::FG_RED);
            return 1;
        }

        $entity = EntityType::findOne($entity_type_id);
        if (!$entity) {
            $this->stdout("Error: Entity type {$entity_type_id} not found\n", Console::FG_RED);
            return 1;
        }

        $this->stdout("=== Generating Atlases for #{$entity->entity_type_id} {$entity->name} ===\n\n", Console::FG_CYAN);

        // Initialize provider registry
        AtlasProviderRegistry::init();

        try {
            // Check if provider exists
            if (!AtlasProviderRegistry::hasProvider($entity)) {
                $this->stdout("Error: No provider for type={$entity->type}, subtype={$entity->subtype}\n", Console::FG_RED);
                return 1;
            }

            // Get provider
            $provider = AtlasProviderRegistry::getProvider($entity);

            // Get atlas generators
            $generators = $provider->getAtlasGenerators($entity);

            if (empty($generators)) {
                $this->stdout("No atlases to generate\n", Console::FG_YELLOW);
                return 0;
            }

            $basePath = Yii::getAlias('@app/..');
            $entityPath = $basePath . '/public/assets/tiles/entities/' . $this->getEntityFolder($entity);

            if (!is_dir($entityPath)) {
                mkdir($entityPath, 0755, true);
            }

            // Generate each atlas
            foreach ($generators as $atlasName => $generator) {
                $atlasGd = $generator->generate();
                $atlasPath = $entityPath . '/' . $atlasName . '.png';

                imagepng($atlasGd, $atlasPath, 9);
                imagedestroy($atlasGd);

                $dims = $generator->getDimensions();
                $this->stdout("✓ Generated {$atlasName}.png ({$dims['width']}×{$dims['height']})\n", Console::FG_GREEN);
            }

            $this->stdout("\nSuccess!\n", Console::FG_GREEN);
            return 0;

        } catch (\Exception $e) {
            $this->stdout("Error: {$e->getMessage()}\n", Console::FG_RED);
            $this->stdout("Stack trace:\n{$e->getTraceAsString()}\n", Console::FG_RED);
            return 1;
        }
    }

    /**
     * Get entity folder path
     * @param EntityType $entity
     * @return string Folder path (e.g., "building/furnace", "conveyor/conveyor")
     */
    private function getEntityFolder(EntityType $entity): string
    {
        // Structure on disk: type/folder
        // Examples:
        //   building/furnace
        //   conveyor/conveyor
        //   pipe/pipe
        //   electricity/pylon_small
        return $entity->type . '/' . $entity->folder;
    }
}
