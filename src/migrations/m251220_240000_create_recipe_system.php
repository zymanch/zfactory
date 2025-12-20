<?php

use yii\db\Migration;

/**
 * Create recipe system:
 * - Add deposit resources (Iron Deposit, Copper Deposit)
 * - Create recipe table (inputs -> output with ticks)
 * - Create entity_type_recipe link table
 * - Fill recipes for all production buildings
 */
class m251220_240000_create_recipe_system extends Migration
{
    public function safeUp()
    {
        // 1. Add deposit resources
        $this->insert('resource', ['resource_id' => 8, 'name' => 'Iron Deposit', 'icon_url' => 'iron_deposit.svg', 'type' => 'raw']);
        $this->insert('resource', ['resource_id' => 9, 'name' => 'Copper Deposit', 'icon_url' => 'copper_deposit.svg', 'type' => 'raw']);

        // 2. Create recipe table
        $this->createTable('recipe', [
            'recipe_id' => $this->primaryKey()->unsigned(),
            'output_resource_id' => $this->integer()->unsigned()->notNull(),
            'output_amount' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'input1_resource_id' => $this->integer()->unsigned()->notNull(),
            'input1_amount' => $this->integer()->unsigned()->notNull()->defaultValue(1),
            'input2_resource_id' => $this->integer()->unsigned()->null(),
            'input2_amount' => $this->integer()->unsigned()->null(),
            'input3_resource_id' => $this->integer()->unsigned()->null(),
            'input3_amount' => $this->integer()->unsigned()->null(),
            'ticks' => $this->integer()->unsigned()->notNull()->defaultValue(60),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        // Add foreign keys for recipe
        $this->addForeignKey('fk_recipe_output', 'recipe', 'output_resource_id', 'resource', 'resource_id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_recipe_input1', 'recipe', 'input1_resource_id', 'resource', 'resource_id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_recipe_input2', 'recipe', 'input2_resource_id', 'resource', 'resource_id', 'RESTRICT', 'CASCADE');
        $this->addForeignKey('fk_recipe_input3', 'recipe', 'input3_resource_id', 'resource', 'resource_id', 'RESTRICT', 'CASCADE');

        // 3. Create entity_type_recipe link table
        $this->createTable('entity_type_recipe', [
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
            'PRIMARY KEY (entity_type_id, recipe_id)',
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addForeignKey('fk_etr_entity_type', 'entity_type_recipe', 'entity_type_id', 'entity_type', 'entity_type_id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_etr_recipe', 'entity_type_recipe', 'recipe_id', 'recipe', 'recipe_id', 'CASCADE', 'CASCADE');

        // 4. Insert recipes
        // Format: (recipe_id, output_resource_id, output_amount, input1_resource_id, input1_amount, input2_resource_id, input2_amount, input3_resource_id, input3_amount, ticks)

        // Mining recipes (for Mining Drill 102, Fast Mining Drill 108)
        $this->insert('recipe', [
            'recipe_id' => 1,
            'output_resource_id' => 2,  // Iron Ore
            'output_amount' => 1,
            'input1_resource_id' => 8,  // Iron Deposit
            'input1_amount' => 1,
            'ticks' => 30,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 2,
            'output_resource_id' => 3,  // Copper Ore
            'output_amount' => 1,
            'input1_resource_id' => 9,  // Copper Deposit
            'input1_amount' => 1,
            'ticks' => 30,
        ]);

        // Furnace recipes (for Small Furnace 101)
        $this->insert('recipe', [
            'recipe_id' => 3,
            'output_resource_id' => 100, // Iron Ingot
            'output_amount' => 1,
            'input1_resource_id' => 2,   // Iron Ore
            'input1_amount' => 3,
            'input2_resource_id' => 4,   // Coal
            'input2_amount' => 1,
            'ticks' => 60,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 4,
            'output_resource_id' => 101, // Copper Ingot
            'output_amount' => 1,
            'input1_resource_id' => 3,   // Copper Ore
            'input1_amount' => 3,
            'input2_resource_id' => 4,   // Coal
            'input2_amount' => 1,
            'ticks' => 60,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 5,
            'output_resource_id' => 109, // Steel Plate
            'output_amount' => 1,
            'input1_resource_id' => 100, // Iron Ingot
            'input1_amount' => 2,
            'input2_resource_id' => 4,   // Coal
            'input2_amount' => 1,
            'ticks' => 90,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 6,
            'output_resource_id' => 112, // Charcoal
            'output_amount' => 1,
            'input1_resource_id' => 1,   // Wood
            'input1_amount' => 1,
            'ticks' => 30,
        ]);

        // Assembly Machine recipes (for Assembly Machine 103)
        $this->insert('recipe', [
            'recipe_id' => 7,
            'output_resource_id' => 102, // Iron Plate
            'output_amount' => 2,
            'input1_resource_id' => 100, // Iron Ingot
            'input1_amount' => 1,
            'ticks' => 40,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 8,
            'output_resource_id' => 103, // Copper Plate
            'output_amount' => 2,
            'input1_resource_id' => 101, // Copper Ingot
            'input1_amount' => 1,
            'ticks' => 40,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 9,
            'output_resource_id' => 104, // Copper Wire
            'output_amount' => 4,
            'input1_resource_id' => 101, // Copper Ingot
            'input1_amount' => 2,
            'ticks' => 20,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 10,
            'output_resource_id' => 105, // Screw
            'output_amount' => 4,
            'input1_resource_id' => 102, // Iron Plate
            'input1_amount' => 2,
            'ticks' => 20,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 11,
            'output_resource_id' => 106, // Gear
            'output_amount' => 1,
            'input1_resource_id' => 102, // Iron Plate
            'input1_amount' => 2,
            'ticks' => 30,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 12,
            'output_resource_id' => 107, // Rotor
            'output_amount' => 1,
            'input1_resource_id' => 106, // Gear
            'input1_amount' => 2,
            'input2_resource_id' => 105, // Screw
            'input2_amount' => 4,
            'ticks' => 60,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 13,
            'output_resource_id' => 110, // Circuit
            'output_amount' => 1,
            'input1_resource_id' => 104, // Copper Wire
            'input1_amount' => 2,
            'input2_resource_id' => 102, // Iron Plate
            'input2_amount' => 1,
            'ticks' => 50,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 14,
            'output_resource_id' => 111, // Motor
            'output_amount' => 1,
            'input1_resource_id' => 107, // Rotor
            'input1_amount' => 1,
            'input2_resource_id' => 110, // Circuit
            'input2_amount' => 2,
            'input3_resource_id' => 104, // Copper Wire
            'input3_amount' => 1,
            'ticks' => 80,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 15,
            'output_resource_id' => 108, // Crystal
            'output_amount' => 1,
            'input1_resource_id' => 6,   // Raw Crystal
            'input1_amount' => 1,
            'ticks' => 45,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 16,
            'output_resource_id' => 113, // Fuel Cell
            'output_amount' => 1,
            'input1_resource_id' => 20,  // Refined Fuel
            'input1_amount' => 2,
            'input2_resource_id' => 110, // Circuit
            'input2_amount' => 1,
            'ticks' => 100,
        ]);

        // Boiler recipes (for Boiler 107)
        $this->insert('recipe', [
            'recipe_id' => 17,
            'output_resource_id' => 22,  // Heavy Oil
            'output_amount' => 1,
            'input1_resource_id' => 7,   // Crude Oil
            'input1_amount' => 1,
            'ticks' => 60,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 18,
            'output_resource_id' => 23,  // Light Oil
            'output_amount' => 1,
            'input1_resource_id' => 22,  // Heavy Oil
            'input1_amount' => 2,
            'ticks' => 40,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 19,
            'output_resource_id' => 20,  // Refined Fuel
            'output_amount' => 1,
            'input1_resource_id' => 23,  // Light Oil
            'input1_amount' => 2,
            'ticks' => 30,
        ]);
        $this->insert('recipe', [
            'recipe_id' => 20,
            'output_resource_id' => 21,  // Lubricant
            'output_amount' => 1,
            'input1_resource_id' => 22,  // Heavy Oil
            'input1_amount' => 3,
            'ticks' => 50,
        ]);

        // 5. Link entity types to recipes
        // Mining Drill (102)
        $this->insert('entity_type_recipe', ['entity_type_id' => 102, 'recipe_id' => 1]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 102, 'recipe_id' => 2]);

        // Fast Mining Drill (108)
        $this->insert('entity_type_recipe', ['entity_type_id' => 108, 'recipe_id' => 1]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 108, 'recipe_id' => 2]);

        // Small Furnace (101)
        $this->insert('entity_type_recipe', ['entity_type_id' => 101, 'recipe_id' => 3]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 101, 'recipe_id' => 4]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 101, 'recipe_id' => 5]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 101, 'recipe_id' => 6]);

        // Assembly Machine (103)
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 7]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 8]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 9]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 10]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 11]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 12]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 13]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 14]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 15]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 103, 'recipe_id' => 16]);

        // Boiler (107)
        $this->insert('entity_type_recipe', ['entity_type_id' => 107, 'recipe_id' => 17]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 107, 'recipe_id' => 18]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 107, 'recipe_id' => 19]);
        $this->insert('entity_type_recipe', ['entity_type_id' => 107, 'recipe_id' => 20]);

        // 6. Update entity_resource for ore deposits to use deposit resources
        // Iron Ore entities (entity_type_id = 300) should contain Iron Deposit (resource_id = 8)
        $this->update('entity_resource',
            ['resource_id' => 8],
            ['resource_id' => 2]  // Was Iron Ore, now Iron Deposit
        );
        // Copper Ore entities (entity_type_id = 301) should contain Copper Deposit (resource_id = 9)
        $this->update('entity_resource',
            ['resource_id' => 9],
            ['resource_id' => 3]  // Was Copper Ore, now Copper Deposit
        );
    }

    public function safeDown()
    {
        // Restore entity_resource
        $this->update('entity_resource', ['resource_id' => 2], ['resource_id' => 8]);
        $this->update('entity_resource', ['resource_id' => 3], ['resource_id' => 9]);

        // Drop tables
        $this->dropTable('entity_type_recipe');
        $this->dropTable('recipe');

        // Remove deposit resources
        $this->delete('resource', ['resource_id' => [8, 9]]);
    }
}
