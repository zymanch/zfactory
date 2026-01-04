<?php

use yii\db\Migration;

/**
 * Migration for conveyor splitter feature
 * Adds:
 * - splitter_state table for storing round-robin state
 * - 3 new splitter types (130-141) × 4 orientations = 12 entity_type records
 */
class m260105_100000_add_conveyor_splitters extends Migration
{
    public function safeUp()
    {
        // Create splitter_state table
        $this->createTable('{{%splitter_state}}', [
            'splitter_state_id' => $this->primaryKey(),
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'last_output_direction' => "ENUM('left', 'right') DEFAULT 'right'",
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add foreign key to entity table
        $this->addForeignKey(
            'fk-splitter_state-entity_id',
            '{{%splitter_state}}',
            'entity_id',
            '{{%entity}}',
            'entity_id',
            'CASCADE',
            'CASCADE'
        );

        // Add index on entity_id for faster lookups
        $this->createIndex(
            'idx-splitter_state-entity_id',
            '{{%splitter_state}}',
            'entity_id'
        );

        // Insert new entity types
        // Base type IDs: 800 (normal), 804 (dual), 808 (fast)
        // Orientations: right, down, left, up (4 total)

        // Splitter Normal - ID 800
        $this->insertSplitterTypes(800, 'Splitter', 'splitter_normal', 100, 'Y-shaped conveyor that splits resources to 2 outputs');

        // Splitter Dual - ID 804
        $this->insertSplitterTypes(804, 'Splitter (Dual)', 'splitter_dual', 100, 'Dual-lane Y-shaped splitter');

        // Fast Splitter - ID 808
        $this->insertSplitterTypes(808, 'Fast Splitter', 'splitter_fast', 200, 'High-speed Y-shaped splitter');
    }

    public function safeDown()
    {
        // Delete entity_type records
        $typeIds = [800, 804, 808];

        foreach ($typeIds as $id) {
            // Delete all orientations (4 total per type)
            for ($i = 0; $i < 4; $i++) {
                $this->delete('{{%entity_type}}', ['entity_type_id' => $id + $i]);
            }
        }

        // Drop foreign key and index
        $this->dropForeignKey('fk-splitter_state-entity_id', '{{%splitter_state}}');
        $this->dropIndex('idx-splitter_state-entity_id', '{{%splitter_state}}');

        // Drop table
        $this->dropTable('{{%splitter_state}}');
    }

    /**
     * Helper method to insert splitter entity types with all orientations
     */
    private function insertSplitterTypes($baseId, $name, $folder, $power, $description)
    {
        // Orientation mapping: right, down, left, up
        $orientations = [
            ['right', 0],
            ['down', 1],
            ['left', 2],
            ['up', 3],
        ];

        foreach ($orientations as $idx => [$orientation, $orientationCode]) {
            $entityTypeId = $baseId + $idx;
            $fullName = $idx === 0 ? $name : "{$name} ({$orientation})";
            $fullFolder = $idx === 0 ? $folder : "{$folder}_{$orientation}";

            $this->insert('{{%entity_type}}', [
                'entity_type_id' => $entityTypeId,
                'type' => 'transporter',
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
                'animation_fps' => $power == 200 ? 8.00 : 4.00,
                'description' => $description,
            ]);
        }
    }
}
