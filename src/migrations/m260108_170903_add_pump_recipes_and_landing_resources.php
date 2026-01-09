<?php

use yii\db\Migration;

/**
 * Class m260108_170903_add_pump_recipes_and_landing_resources
 */
class m260108_170903_add_pump_recipes_and_landing_resources extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add deposit resources for fluid sources (these are "mined" by pumps)
        $this->batchInsert('resource', ['resource_id', 'name', 'icon_url', 'type'], [
            [400, 'Water Source', 'water_source.svg', 'deposit'],
            [401, 'Oil Well', 'oil_well.svg', 'deposit'],
            [402, 'Gas Vent', 'gas_vent.svg', 'deposit'],
            [403, 'Lava Pool', 'lava_pool.svg', 'deposit'],
        ]);

        // Add recipes for fluid extraction (deposit -> fluid)
        // Note: input1 is the deposit resource, output is the fluid
        // Use auto-increment for recipe_id
        $this->batchInsert('recipe',
            ['output_resource_id', 'output_amount', 'input1_resource_id', 'input1_amount', 'ticks'],
            [
                [300, 1, 400, 1, 30], // Water Source -> Water (0.5s)
                [301, 1, 401, 1, 30], // Oil Well -> Crude Oil (0.5s)
                [302, 1, 402, 1, 30], // Gas Vent -> Natural Gas (0.5s)
                [303, 1, 403, 1, 30], // Lava Pool -> Lava (0.5s)
            ]
        );

        // Get the auto-generated recipe IDs (last 4 inserted)
        $lastRecipeId = $this->db->createCommand('SELECT MAX(recipe_id) FROM recipe')->queryScalar();
        $recipeIds = [
            $lastRecipeId - 3, // Water
            $lastRecipeId - 2, // Oil
            $lastRecipeId - 1, // Gas
            $lastRecipeId,      // Lava
        ];

        // Link pumps to their recipes
        $this->batchInsert('entity_type_recipe', ['entity_type_id', 'recipe_id'], [
            [145, $recipeIds[0]], // Water Pump -> Water extraction
            [146, $recipeIds[1]], // Oil Pump -> Oil extraction
            [147, $recipeIds[2]], // Gas Pump -> Gas extraction
            [148, $recipeIds[3]], // Lava Pump -> Lava extraction
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Delete pump entity_type_recipe links (entity_type_id 145-148)
        $this->delete('entity_type_recipe', ['entity_type_id' => [145, 146, 147, 148]]);

        // Delete recipes that have output resources 300-303 (fluid resources)
        $this->delete('recipe', ['output_resource_id' => [300, 301, 302, 303]]);

        // Delete deposit resources
        $this->delete('resource', ['resource_id' => [400, 401, 402, 403]]);

        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260108_170903_add_pump_recipes_and_landing_resources cannot be reverted.\n";

        return false;
    }
    */
}
