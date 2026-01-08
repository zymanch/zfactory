<?php

use yii\db\Migration;

/**
 * Class m260108_082418_add_press_recipes
 */
class m260108_082418_add_press_recipes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $pressEntityTypeId = 115;

        // Recipe 27: Iron Rod (1 Iron Ingot → 2 Iron Rods)
        $this->insert('recipe', [
            'recipe_id' => 27,
            'output_resource_id' => 204, // Iron Rod
            'output_amount' => 2,
            'input1_resource_id' => 100, // Iron Ingot
            'input1_amount' => 1,
            'input2_resource_id' => null,
            'input2_amount' => null,
            'input3_resource_id' => null,
            'input3_amount' => null,
            'ticks' => 120, // 2 seconds
        ]);
        $this->insert('entity_type_recipe', [
            'entity_type_id' => $pressEntityTypeId,
            'recipe_id' => 27,
        ]);

        // Recipe 28: Copper Rod (1 Copper Ingot → 2 Copper Rods)
        $this->insert('recipe', [
            'recipe_id' => 28,
            'output_resource_id' => 205, // Copper Rod
            'output_amount' => 2,
            'input1_resource_id' => 101, // Copper Ingot
            'input1_amount' => 1,
            'input2_resource_id' => null,
            'input2_amount' => null,
            'input3_resource_id' => null,
            'input3_amount' => null,
            'ticks' => 120, // 2 seconds
        ]);
        $this->insert('entity_type_recipe', [
            'entity_type_id' => $pressEntityTypeId,
            'recipe_id' => 28,
        ]);

        // Recipe 29: Iron Plate (2 Iron Ingots → 1 Iron Plate)
        $this->insert('recipe', [
            'recipe_id' => 29,
            'output_resource_id' => 102, // Iron Plate
            'output_amount' => 1,
            'input1_resource_id' => 100, // Iron Ingot
            'input1_amount' => 2,
            'input2_resource_id' => null,
            'input2_amount' => null,
            'input3_resource_id' => null,
            'input3_amount' => null,
            'ticks' => 180, // 3 seconds
        ]);
        $this->insert('entity_type_recipe', [
            'entity_type_id' => $pressEntityTypeId,
            'recipe_id' => 29,
        ]);

        // Recipe 30: Copper Plate (2 Copper Ingots → 1 Copper Plate)
        $this->insert('recipe', [
            'recipe_id' => 30,
            'output_resource_id' => 103, // Copper Plate
            'output_amount' => 1,
            'input1_resource_id' => 101, // Copper Ingot
            'input1_amount' => 2,
            'input2_resource_id' => null,
            'input2_amount' => null,
            'input3_resource_id' => null,
            'input3_amount' => null,
            'ticks' => 180, // 3 seconds
        ]);
        $this->insert('entity_type_recipe', [
            'entity_type_id' => $pressEntityTypeId,
            'recipe_id' => 30,
        ]);

        // Recipe 31: Copper Wire (1 Copper Ingot → 4 Copper Wires)
        $this->insert('recipe', [
            'recipe_id' => 31,
            'output_resource_id' => 104, // Copper Wire
            'output_amount' => 4,
            'input1_resource_id' => 101, // Copper Ingot
            'input1_amount' => 1,
            'input2_resource_id' => null,
            'input2_amount' => null,
            'input3_resource_id' => null,
            'input3_amount' => null,
            'ticks' => 120, // 2 seconds
        ]);
        $this->insert('entity_type_recipe', [
            'entity_type_id' => $pressEntityTypeId,
            'recipe_id' => 31,
        ]);

        // Recipe 32: Screw (1 Iron Rod → 4 Screws)
        $this->insert('recipe', [
            'recipe_id' => 32,
            'output_resource_id' => 105, // Screw
            'output_amount' => 4,
            'input1_resource_id' => 204, // Iron Rod
            'input1_amount' => 1,
            'input2_resource_id' => null,
            'input2_amount' => null,
            'input3_resource_id' => null,
            'input3_amount' => null,
            'ticks' => 120, // 2 seconds
        ]);
        $this->insert('entity_type_recipe', [
            'entity_type_id' => $pressEntityTypeId,
            'recipe_id' => 32,
        ]);

        // Recipe 33: Bolt (1 Iron Rod + 1 Screw → 2 Bolts)
        $this->insert('recipe', [
            'recipe_id' => 33,
            'output_resource_id' => 206, // Bolt
            'output_amount' => 2,
            'input1_resource_id' => 204, // Iron Rod
            'input1_amount' => 1,
            'input2_resource_id' => 105, // Screw
            'input2_amount' => 1,
            'input3_resource_id' => null,
            'input3_amount' => null,
            'ticks' => 180, // 3 seconds
        ]);
        $this->insert('entity_type_recipe', [
            'entity_type_id' => $pressEntityTypeId,
            'recipe_id' => 33,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Delete entity_type_recipe links
        for ($i = 27; $i <= 33; $i++) {
            $this->delete('entity_type_recipe', ['recipe_id' => $i]);
        }

        // Delete recipes
        for ($i = 27; $i <= 33; $i++) {
            $this->delete('recipe', ['recipe_id' => $i]);
        }
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260108_082418_add_press_recipes cannot be reverted.\n";

        return false;
    }
    */
}
