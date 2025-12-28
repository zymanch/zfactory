<?php

use yii\db\Migration;

/**
 * Добавляет рецепты для новых добывающих зданий
 * - Sawmill: Wood → Wood
 * - Stone Quarry: Stone → Stone
 * - Mine: Silver/Gold Deposit → Silver/Gold Ore
 * - Quarry: Aluminum/Titanium Deposit → Aluminum/Titanium Ore
 * - Добавляет рецепты к обновленным Drill (102, 108, 506)
 */
class m251228_130000_add_extraction_recipes extends Migration
{
    public function safeUp()
    {
        // 1. Создать новые рецепты для добычи
        $this->batchInsert('recipe', [
            'recipe_id', 'output_resource_id', 'output_amount', 'input1_resource_id', 'input1_amount',
            'input2_resource_id', 'input2_amount', 'input3_resource_id', 'input3_amount', 'ticks'
        ], [
            // Sawmill recipes (Wood → Wood, прямая добыча)
            [21, 1, 1, 1, 1, NULL, NULL, NULL, NULL, 30],  // 1 Wood → 1 Wood (0.5s)

            // Stone Quarry recipes (Stone → Stone, прямая добыча)
            [22, 5, 1, 5, 1, NULL, NULL, NULL, NULL, 30],  // 1 Stone → 1 Stone (0.5s)

            // Mine recipes (драгоценные металлы)
            [23, 16, 1, 12, 1, NULL, NULL, NULL, NULL, 45], // 1 Silver Deposit → 1 Silver Ore (0.75s)
            [24, 17, 1, 13, 1, NULL, NULL, NULL, NULL, 45], // 1 Gold Deposit → 1 Gold Ore (0.75s)

            // Quarry recipes (промышленные металлы)
            [25, 14, 1, 10, 1, NULL, NULL, NULL, NULL, 40], // 1 Aluminum Deposit → 1 Aluminum Ore (0.67s)
            [26, 15, 1, 11, 1, NULL, NULL, NULL, NULL, 40], // 1 Titanium Deposit → 1 Titanium Ore (0.67s)
        ]);

        // 2. Привязать рецепты к entity_type через entity_type_recipe
        $this->batchInsert('entity_type_recipe', ['entity_type_id', 'recipe_id'], [
            // Sawmills (500-502) - все могут добывать дерево
            [500, 21], [501, 21], [502, 21],

            // Stone Quarries (503-505) - все могут добывать камень
            [503, 22], [504, 22], [505, 22],

            // Large Ore Drill (506) - добавить существующие рецепты руды
            [506, 1], [506, 2],

            // Mines (507-509) - могут добывать серебро и золото
            [507, 23], [507, 24],
            [508, 23], [508, 24],
            [509, 23], [509, 24],

            // Quarries (510-512) - могут добывать алюминий и титан
            [510, 25], [510, 26],
            [511, 25], [511, 26],
            [512, 25], [512, 26],
        ]);
    }

    public function safeDown()
    {
        // Удалить привязки рецептов
        $this->delete('entity_type_recipe', ['recipe_id' => [21, 22, 23, 24, 25, 26]]);
        $this->delete('entity_type_recipe', ['entity_type_id' => [506]]);

        // Удалить рецепты
        $this->delete('recipe', ['recipe_id' => [21, 22, 23, 24, 25, 26]]);
    }
}
