<?php

use yii\db\Migration;

/**
 * Добавляет новые добывающие здания и обновляет буровые установки
 * - Лесопилки (sawmill) для деревьев: 500-502
 * - Камнеломни (stone quarry) для камней: 503-505
 * - Обновленные буровые установки + новая: 102, 108, 506
 * - Шахты (mine) для серебра/золота: 507-509
 * - Карьеры (quarry) для алюминия/титана: 510-512
 */
class m251228_120000_add_extraction_buildings extends Migration
{
    public function safeUp()
    {
        // 1. Обновить названия существующих буровых установок
        $this->update('entity_type',
            ['name' => 'Small Ore Drill', 'description' => 'Небольшая буровая установка. Добывает руду из месторождений.'],
            ['entity_type_id' => 102]
        );

        $this->update('entity_type',
            ['name' => 'Medium Ore Drill', 'description' => 'Средняя буровая установка. Добывает руду быстрее обычной.', 'width' => 2, 'height' => 2],
            ['entity_type_id' => 108]
        );

        // 2. Добавить новые добывающие здания
        $this->batchInsert('entity_type', [
            'entity_type_id', 'type', 'name', 'description', 'image_url', 'extension',
            'max_durability', 'width', 'height', 'icon_url', 'power',
            'parent_entity_type_id', 'orientation', 'animation_fps', 'construction_ticks'
        ], [
            // Лесопилки (Sawmill) - для добычи дерева
            [500, 'mining', 'Small Sawmill', 'Небольшая лесопилка. Добывает древесину из деревьев.',
                'sawmill_small', 'png', 200, 1, 1, 'sawmill_small/normal.png', 100, NULL, 'none', NULL, 60],
            [501, 'mining', 'Medium Sawmill', 'Средняя лесопилка. Добывает древесину быстрее.',
                'sawmill_medium', 'png', 400, 3, 3, 'sawmill_medium/normal.png', 150, NULL, 'none', NULL, 180],
            [502, 'mining', 'Large Sawmill', 'Большая лесопилка. Добывает древесину очень быстро.',
                'sawmill_large', 'png', 600, 5, 5, 'sawmill_large/normal.png', 200, NULL, 'none', NULL, 300],

            // Камнеломни (Stone Quarry) - для добычи камня
            [503, 'mining', 'Small Stone Quarry', 'Небольшая камнеломня. Добывает камень.',
                'stone_quarry_small', 'png', 250, 1, 1, 'stone_quarry_small/normal.png', 100, NULL, 'none', NULL, 60],
            [504, 'mining', 'Medium Stone Quarry', 'Средняя камнеломня. Добывает камень быстрее.',
                'stone_quarry_medium', 'png', 500, 3, 3, 'stone_quarry_medium/normal.png', 150, NULL, 'none', NULL, 180],
            [505, 'mining', 'Large Stone Quarry', 'Большая камнеломня. Добывает камень очень быстро.',
                'stone_quarry_large', 'png', 750, 5, 5, 'stone_quarry_large/normal.png', 200, NULL, 'none', NULL, 300],

            // Буровая установка Large (дополнение к 102, 108)
            [506, 'mining', 'Large Ore Drill', 'Большая буровая установка. Добывает руду очень быстро.',
                'drill_large', 'png', 500, 3, 3, 'drill_large/normal.png', 200, NULL, 'none', NULL, 180],

            // Шахты (Mine) - для серебра и золота
            [507, 'mining', 'Small Mine', 'Небольшая шахта. Добывает драгоценные металлы.',
                'mine_small', 'png', 300, 1, 1, 'mine_small/normal.png', 100, NULL, 'none', NULL, 90],
            [508, 'mining', 'Medium Mine', 'Средняя шахта. Добывает драгоценные металлы быстрее.',
                'mine_medium', 'png', 600, 2, 2, 'mine_medium/normal.png', 150, NULL, 'none', NULL, 180],
            [509, 'mining', 'Large Mine', 'Большая шахта. Добывает драгоценные металлы очень быстро.',
                'mine_large', 'png', 900, 3, 3, 'mine_large/normal.png', 200, NULL, 'none', NULL, 270],

            // Карьеры (Quarry) - для алюминия и титана
            [510, 'mining', 'Small Quarry', 'Небольшой карьер. Добывает алюминий и титан.',
                'quarry_small', 'png', 300, 1, 1, 'quarry_small/normal.png', 100, NULL, 'none', NULL, 90],
            [511, 'mining', 'Medium Quarry', 'Средний карьер. Добывает алюминий и титан быстрее.',
                'quarry_medium', 'png', 600, 2, 2, 'quarry_medium/normal.png', 150, NULL, 'none', NULL, 180],
            [512, 'mining', 'Large Quarry', 'Большой карьер. Добывает алюминий и титан очень быстро.',
                'quarry_large', 'png', 900, 3, 3, 'quarry_large/normal.png', 200, NULL, 'none', NULL, 270],
        ]);
    }

    public function safeDown()
    {
        // Удалить новые здания
        $this->delete('entity_type', ['entity_type_id' => [500, 501, 502, 503, 504, 505, 506, 507, 508, 509, 510, 511, 512]]);

        // Вернуть старые названия буровых установок
        $this->update('entity_type',
            ['name' => 'Mining Drill', 'description' => 'Буровая установка. Добывает руду из месторождений.'],
            ['entity_type_id' => 102]
        );

        $this->update('entity_type',
            ['name' => 'Fast Mining Drill', 'description' => 'Быстрая буровая установка. Добывает руду быстрее обычной.', 'width' => 1, 'height' => 1],
            ['entity_type_id' => 108]
        );
    }
}
