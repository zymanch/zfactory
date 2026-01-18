<?php

namespace commands\entity;

use Yii;
use yii\base\Action;
use yii\helpers\Console;

/**
 * Migrate sprites from old structure (normal.png) to new (sprite.png)
 * Usage: php yii entity/migrate-sprites
 */
class MigrateSprites extends Action
{
    public function run()
    {
        Console::stdout("=== Migrating Entity Sprites ===\n\n", Console::FG_CYAN);

        $basePath = Yii::getAlias('@app/..');

        // Use direct SQL to avoid STI issues
        $sql = "SELECT entity_type_id, type, folder, parent_entity_type_id FROM entity_type ORDER BY entity_type_id";
        $entityTypes = Yii::$app->db->createCommand($sql)->queryAll();

        $migrated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($entityTypes as $entity) {
            $result = $this->migrateSprite($entity, $basePath);

            switch ($result) {
                case 'migrated':
                    $migrated++;
                    break;
                case 'skipped':
                    $skipped++;
                    break;
                case 'error':
                    $errors++;
                    break;
            }
        }

        Console::stdout("\n=== Migration Complete ===\n", Console::FG_CYAN);
        Console::stdout("  Migrated: {$migrated}\n", Console::FG_GREEN);
        Console::stdout("  Skipped: {$skipped}\n", Console::FG_YELLOW);
        if ($errors > 0) {
            Console::stdout("  Errors: {$errors}\n", Console::FG_RED);
        }

        return 0;
    }

    /**
     * Migrate sprite for single entity
     * @param array $entity Entity data from database
     * @param string $basePath
     * @return string 'migrated', 'skipped', or 'error'
     */
    private function migrateSprite(array $entity, string $basePath): string
    {
        $folder = $this->getEntityFolder($entity);
        $entityPath = $basePath . '/public/assets/tiles/entities/' . $folder;

        if (!is_dir($entityPath)) {
            Console::stdout("Warning: {$folder} not found\n", Console::FG_YELLOW);
            return 'error';
        }

        // Skip rotational variants (they will be generated from parent)
        if ($entity['parent_entity_type_id']) {
            Console::stdout("Skipping {$folder} (rotational variant)\n", Console::FG_CYAN);
            return 'skipped';
        }

        // Skip if sprite.png already exists
        $spritePath = $entityPath . '/sprite.png';
        if (file_exists($spritePath)) {
            Console::stdout("Skipping {$folder} (sprite.png exists)\n", Console::FG_CYAN);
            return 'skipped';
        }

        // Copy normal.png to sprite.png
        $normalPath = $entityPath . '/normal.png';
        if (!file_exists($normalPath)) {
            Console::stdout("Warning: {$folder}/normal.png not found\n", Console::FG_YELLOW);
            return 'error';
        }

        if (!copy($normalPath, $spritePath)) {
            Console::stdout("Error: Failed to copy {$folder}/normal.png\n", Console::FG_RED);
            return 'error';
        }

        Console::stdout("Migrated: {$folder}/normal.png → sprite.png\n", Console::FG_GREEN);
        return 'migrated';
    }

    /**
     * Get entity folder path
     * @param array $entity Entity data
     * @return string Folder path (e.g., "building/furnace", "conveyor/conveyor")
     */
    private function getEntityFolder(array $entity): string
    {
        $folder = $entity['folder'];
        $type = $entity['type'];

        // Structure on disk: type/folder
        // Examples:
        //   building/furnace
        //   conveyor/conveyor
        //   pipe/pipe
        //   electricity/pylon_small
        return $type . '/' . $folder;
    }
}
