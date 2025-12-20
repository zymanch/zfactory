<?php

use yii\db\Migration;

/**
 * Creates table `resource` - game resources (ores, ingots, crafted items, fuels)
 */
class m251219_125900_create_resource_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('resource', [
            'resource_id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(128)->notNull(),
            'icon_url' => $this->string(256)->notNull(),
            'type' => "ENUM('raw', 'liquid', 'crafted') NOT NULL DEFAULT 'raw'",
        ]);

        // Insert initial resources
        $this->batchInsert('resource', ['resource_id', 'name', 'icon_url', 'type'], [
            // Raw resources (ores, wood, coal)
            [1, 'Wood', 'wood.svg', 'raw'],
            [2, 'Iron Ore', 'iron_ore.svg', 'raw'],
            [3, 'Copper Ore', 'copper_ore.svg', 'raw'],
            [4, 'Coal', 'coal.svg', 'raw'],
            [5, 'Stone', 'stone.svg', 'raw'],
            [6, 'Raw Crystal', 'raw_crystal.svg', 'raw'],
            [7, 'Crude Oil', 'crude_oil.svg', 'raw'],

            // Liquid resources (fuels, lubricants)
            [20, 'Refined Fuel', 'refined_fuel.svg', 'liquid'],
            [21, 'Lubricant', 'lubricant.svg', 'liquid'],
            [22, 'Heavy Oil', 'heavy_oil.svg', 'liquid'],
            [23, 'Light Oil', 'light_oil.svg', 'liquid'],

            // Crafted resources (ingots, plates, components)
            [100, 'Iron Ingot', 'iron_ingot.svg', 'crafted'],
            [101, 'Copper Ingot', 'copper_ingot.svg', 'crafted'],
            [102, 'Iron Plate', 'iron_plate.svg', 'crafted'],
            [103, 'Copper Plate', 'copper_plate.svg', 'crafted'],
            [104, 'Copper Wire', 'copper_wire.svg', 'crafted'],
            [105, 'Screw', 'screw.svg', 'crafted'],
            [106, 'Gear', 'gear.svg', 'crafted'],
            [107, 'Rotor', 'rotor.svg', 'crafted'],
            [108, 'Crystal', 'crystal.svg', 'crafted'],
            [109, 'Steel Plate', 'steel_plate.svg', 'crafted'],
            [110, 'Circuit', 'circuit.svg', 'crafted'],
            [111, 'Motor', 'motor.svg', 'crafted'],
            [112, 'Charcoal', 'charcoal.svg', 'crafted'],
            [113, 'Fuel Cell', 'fuel_cell.svg', 'crafted'],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('resource');
    }
}
