<?php

use yii\db\Migration;

/**
 * Insert missing entity_types that are required by later migrations
 * (specifically m251220_240000_create_recipe_system which references 101, 102, 103, 107, 108)
 */
class m251220_235000_insert_missing_entity_types extends Migration
{
    public function safeUp()
    {
        // Check which entity_types already exist
        $existing = $this->db->createCommand('SELECT entity_type_id FROM entity_type WHERE entity_type_id IN (100, 101, 102, 103, 104, 105, 106, 107)')
            ->queryColumn();

        $toInsert = [
            // Conveyor Belt (transporter)
            100 => [
                'entity_type_id' => 100,
                'type' => 'transporter',
                'name' => 'Conveyor Belt',
                'image_url' => 'conveyor',
                'extension' => 'png',
                'max_durability' => 100,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'conveyor/normal.png',
                'power' => 100,
                'parent_entity_type_id' => null,
                'orientation' => 'right',
            ],
            // Small Furnace (building)
            101 => [
                'entity_type_id' => 101,
                'type' => 'building',
                'name' => 'Small Furnace',
                'image_url' => 'furnace',
                'extension' => 'png',
                'max_durability' => 200,
                'width' => 2,
                'height' => 2,
                'icon_url' => 'furnace/normal.png',
                'power' => 100,
                'parent_entity_type_id' => null,
                'orientation' => 'none',
            ],
            // Small Ore Drill (mining)
            102 => [
                'entity_type_id' => 102,
                'type' => 'mining',
                'name' => 'Small Ore Drill',
                'image_url' => 'drill',
                'extension' => 'png',
                'max_durability' => 300,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'drill/normal.png',
                'power' => 100,
                'parent_entity_type_id' => null,
                'orientation' => 'none',
            ],
            // Assembly Machine (building)
            103 => [
                'entity_type_id' => 103,
                'type' => 'building',
                'name' => 'Assembly Machine',
                'image_url' => 'assembler',
                'extension' => 'png',
                'max_durability' => 400,
                'width' => 3,
                'height' => 3,
                'icon_url' => 'assembler/normal.png',
                'power' => 100,
                'parent_entity_type_id' => null,
                'orientation' => 'none',
            ],
            // Storage Chest (building for now, will be updated to 'storage' later)
            104 => [
                'entity_type_id' => 104,
                'type' => 'building',
                'name' => 'Storage Chest',
                'image_url' => 'chest',
                'extension' => 'png',
                'max_durability' => 150,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'chest/normal.png',
                'power' => 1,
                'parent_entity_type_id' => null,
                'orientation' => 'none',
            ],
            // Power Pole (building)
            105 => [
                'entity_type_id' => 105,
                'type' => 'building',
                'name' => 'Power Pole',
                'image_url' => 'power_pole',
                'extension' => 'png',
                'max_durability' => 100,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'power_pole/normal.png',
                'power' => 1,
                'parent_entity_type_id' => null,
                'orientation' => 'none',
            ],
            // Steam Engine (building)
            106 => [
                'entity_type_id' => 106,
                'type' => 'building',
                'name' => 'Steam Engine',
                'image_url' => 'steam_engine',
                'extension' => 'png',
                'max_durability' => 350,
                'width' => 2,
                'height' => 3,
                'icon_url' => 'steam_engine/normal.png',
                'power' => 1,
                'parent_entity_type_id' => null,
                'orientation' => 'none',
            ],
            // Boiler (building)
            107 => [
                'entity_type_id' => 107,
                'type' => 'building',
                'name' => 'Boiler',
                'image_url' => 'boiler',
                'extension' => 'png',
                'max_durability' => 250,
                'width' => 2,
                'height' => 2,
                'icon_url' => 'boiler/normal.png',
                'power' => 100,
                'parent_entity_type_id' => null,
                'orientation' => 'none',
            ],
        ];

        foreach ($toInsert as $id => $data) {
            if (!in_array($id, $existing)) {
                $this->insert('entity_type', $data);
            }
        }

        $this->execute("INSERT IGNORE INTO `landing` (`landing_id`, `is_buildable`, `name`, `folder`) VALUES
            (1, 'yes', 'Grass', 'grass'),
            (2, 'yes', 'Dirt', 'dirt'),
            (3, 'yes', 'Sand', 'sand'),
            (4, 'no', 'Water', 'water'),
            (5, 'no', 'Stone', 'stone'),
            (6, 'no', 'Lava', 'lava'),
            (7, 'yes', 'Snow', 'snow'),
            (8, 'no', 'Swamp', 'swamp'),
            (9, 'no', 'Sky', 'sky'),
            (10, 'no', 'Island Edge', 'island_edge')");
    }

    public function safeDown()
    {
        // Only delete if they were inserted by this migration
        // (don't delete if they existed before)
        $this->delete('entity_type', ['entity_type_id' => [100, 101, 102, 103, 104, 105, 106, 107]]);
    }
}
