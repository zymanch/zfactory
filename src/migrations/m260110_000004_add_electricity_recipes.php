<?php

use yii\db\Migration;

/**
 * Add recipes for electricity generators
 */
class m260110_000004_add_electricity_recipes extends Migration
{
    public function safeUp()
    {
        // 1. Add recipes
        $this->batchInsert('{{%recipe}}',
            ['recipe_id', 'input1_resource_id', 'input1_amount', 'input2_resource_id', 'input2_amount', 'input3_resource_id', 'input3_amount', 'output_resource_id', 'output_amount', 'ticks'],
            [
                // Coal Generator: Coal(7) → Electricity(400)
                [500, 7, 1, NULL, NULL, NULL, NULL, 400, 10, 60],

                // Solar Panel Small: Sunlight(401) → Electricity(400) [amount=0 means not consumed]
                [501, 401, 0, NULL, NULL, NULL, NULL, 400, 5, 60],

                // Solar Panel Large: Sunlight(401) → Electricity(400) [amount=0 means not consumed]
                [502, 401, 0, NULL, NULL, NULL, NULL, 400, 25, 60],
            ]
        );

        // 2. Link recipes to entity types
        $this->batchInsert('{{%entity_type_recipe}}',
            ['entity_type_id', 'recipe_id'],
            [
                [920, 500], // Coal Generator → Coal recipe
                [921, 501], // Solar Panel Small → Solar Small recipe
                [922, 502], // Solar Panel Large → Solar Large recipe
            ]
        );
    }

    public function safeDown()
    {
        // Delete entity_type_recipe links
        $this->delete('{{%entity_type_recipe}}', ['IN', 'recipe_id', [500, 501, 502]]);

        // Delete recipes
        $this->delete('{{%recipe}}', ['IN', 'recipe_id', [500, 501, 502]]);
    }
}
