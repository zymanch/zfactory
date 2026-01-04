<?php

use yii\db\Migration;

/**
 * Migration for filtered manipulators feature
 * Adds:
 * - manipulator_config table for storing filter settings
 * - 3 new manipulator types (216, 220, 221) × 6 orientations = 18 entity_type records
 */
class m260105_000000_add_filtered_manipulators extends Migration
{
    public function safeUp()
    {
        // Create manipulator_config table
        $this->createTable('{{%manipulator_config}}', [
            'manipulator_config_id' => $this->primaryKey(),
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'filter_resource_ids' => $this->text()->comment('JSON array of resource IDs to filter'),
            'max_transfer_count' => $this->integer()->unsigned()->null()->comment('Max items to transfer (for counting manipulator)'),
            'current_transfer_count' => $this->integer()->unsigned()->defaultValue(0)->comment('Current transfer count'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add foreign key to entity table
        $this->addForeignKey(
            'fk-manipulator_config-entity_id',
            '{{%manipulator_config}}',
            'entity_id',
            '{{%entity}}',
            'entity_id',
            'CASCADE',
            'CASCADE'
        );

        // Add index on entity_id for faster lookups
        $this->createIndex(
            'idx-manipulator_config-entity_id',
            '{{%manipulator_config}}',
            'entity_id'
        );

        // Insert new entity types
        // Base type IDs: 216 (1 filter), 220 (5 filters), 221 (1 filter + counter)
        // Orientations: base + 5 rotated versions

        // Filtered Manipulator (1 Filter) - ID 216
        $this->insertManipulatorTypes(216, 'Filtered Manipulator (1F)', 'manipulator_filtered_1f', 100, 1);

        // Filtered Manipulator (5 Filters) - ID 220
        $this->insertManipulatorTypes(220, 'Filtered Manipulator (5F)', 'manipulator_filtered_5f', 100, 5);

        // Counting Manipulator (1 Filter + Counter) - ID 221
        $this->insertManipulatorTypes(221, 'Counting Manipulator (1F)', 'manipulator_counting_1f', 100, 1);
    }

    public function safeDown()
    {
        // Delete entity_type records
        $typeIds = [
            216, 217, 218, 219, // Filtered 1F (4 orientations)
            220, // Filtered 5F base
            221, // Counting 1F base
        ];

        foreach ($typeIds as $id) {
            // Delete all orientations (6 total per type)
            for ($i = 0; $i < 6; $i++) {
                $this->delete('{{%entity_type}}', ['entity_type_id' => $id + $i * 100]);
            }
        }

        // Drop foreign key and index
        $this->dropForeignKey('fk-manipulator_config-entity_id', '{{%manipulator_config}}');
        $this->dropIndex('idx-manipulator_config-entity_id', '{{%manipulator_config}}');

        // Drop table
        $this->dropTable('{{%manipulator_config}}');
    }

    /**
     * Helper method to insert manipulator entity types with all orientations
     */
    private function insertManipulatorTypes($baseId, $name, $folder, $power, $filterCount)
    {
        // Orientation mapping: right, down, left, up, right-up, right-down
        $orientations = [
            ['right', 0],
            ['down', 1],
            ['left', 2],
            ['up', 3],
            ['right-up', 4],
            ['right-down', 5],
        ];

        foreach ($orientations as $idx => [$orientation, $orientationCode]) {
            $entityTypeId = $baseId + ($idx > 0 ? $idx * 100 : 0);
            $fullName = $idx === 0 ? $name : "{$name} ({$orientation})";
            $fullFolder = $idx === 0 ? $folder : "{$folder}_{$orientation}";

            $this->insert('{{%entity_type}}', [
                'entity_type_id' => $entityTypeId,
                'type' => 'manipulator',
                'name' => $fullName,
                'image_url' => $fullFolder,
                'extension' => 'png',
                'max_durability' => 100,
                'width' => 1,
                'height' => 1,
                'icon_url' => $fullFolder . '/normal.png',
                'power' => $power,
                'parent_entity_type_id' => $idx === 0 ? null : $baseId,
                'orientation' => $orientation,
                'animation_fps' => null,
                'description' => $filterCount > 1
                    ? "Manipulator with {$filterCount} resource filters"
                    : ($baseId === 221
                        ? "Manipulator with filter and transfer counter"
                        : "Manipulator with resource filter"),
            ]);
        }
    }
}
