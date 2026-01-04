<?php

use yii\db\Migration;

/**
 * Migration for underground conveyor feature
 * Adds:
 * - underground_link table for entrance-exit pairing
 * - 6 new underground conveyor types (142-165) × 4 orientations = 24 entity_type records
 */
class m260105_200000_add_underground_conveyors extends Migration
{
    public function safeUp()
    {
        // Create underground_link table
        $this->createTable('{{%underground_link}}', [
            'underground_link_id' => $this->primaryKey(),
            'entrance_entity_id' => $this->integer()->unsigned()->notNull(),
            'exit_entity_id' => $this->integer()->unsigned()->null(),
            'distance' => $this->tinyInteger()->unsigned()->comment('Distance in tiles (1-4)'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add foreign keys
        $this->addForeignKey(
            'fk-underground_link-entrance_entity_id',
            '{{%underground_link}}',
            'entrance_entity_id',
            '{{%entity}}',
            'entity_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-underground_link-exit_entity_id',
            '{{%underground_link}}',
            'exit_entity_id',
            '{{%entity}}',
            'entity_id',
            'SET NULL',
            'CASCADE'
        );

        // Add indexes
        $this->createIndex(
            'idx-underground_link-entrance_entity_id',
            '{{%underground_link}}',
            'entrance_entity_id'
        );

        $this->createIndex(
            'idx-underground_link-exit_entity_id',
            '{{%underground_link}}',
            'exit_entity_id'
        );

        // Insert new entity types
        // Base type IDs:
        // - 812: Underground Belt In (normal)
        // - 816: Underground Belt Out (normal)
        // - 820: Underground Belt Dual In
        // - 824: Underground Belt Dual Out
        // - 828: Fast Underground Belt In
        // - 832: Fast Underground Belt Out

        // Underground Belt (Normal) - In
        $this->insertUndergroundTypes(812, 'Underground Conveyor (In)', 'underground_belt_in', 100, 'Entrance for underground conveyor (1-4 tiles)');

        // Underground Belt (Normal) - Out
        $this->insertUndergroundTypes(816, 'Underground Conveyor (Out)', 'underground_belt_out', 100, 'Exit for underground conveyor');

        // Underground Belt (Dual) - In
        $this->insertUndergroundTypes(820, 'Underground Conveyor Dual (In)', 'underground_belt_dual_in', 100, 'Dual-lane underground entrance');

        // Underground Belt (Dual) - Out
        $this->insertUndergroundTypes(824, 'Underground Conveyor Dual (Out)', 'underground_belt_dual_out', 100, 'Dual-lane underground exit');

        // Fast Underground Belt - In
        $this->insertUndergroundTypes(828, 'Fast Underground Conveyor (In)', 'underground_belt_fast_in', 200, 'High-speed underground entrance');

        // Fast Underground Belt - Out
        $this->insertUndergroundTypes(832, 'Fast Underground Conveyor (Out)', 'underground_belt_fast_out', 200, 'High-speed underground exit');
    }

    public function safeDown()
    {
        // Delete entity_type records
        $typeIds = [812, 816, 820, 824, 828, 832];

        foreach ($typeIds as $id) {
            // Delete all orientations (4 total per type)
            for ($i = 0; $i < 4; $i++) {
                $this->delete('{{%entity_type}}', ['entity_type_id' => $id + $i]);
            }
        }

        // Drop foreign keys and indexes
        $this->dropForeignKey('fk-underground_link-entrance_entity_id', '{{%underground_link}}');
        $this->dropForeignKey('fk-underground_link-exit_entity_id', '{{%underground_link}}');
        $this->dropIndex('idx-underground_link-entrance_entity_id', '{{%underground_link}}');
        $this->dropIndex('idx-underground_link-exit_entity_id', '{{%underground_link}}');

        // Drop table
        $this->dropTable('{{%underground_link}}');
    }

    /**
     * Helper method to insert underground conveyor entity types with all orientations
     */
    private function insertUndergroundTypes($baseId, $name, $folder, $power, $description)
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
