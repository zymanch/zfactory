<?php

use yii\db\Migration;

/**
 * Добавление научных пакетов (Science Packs) как ресурсов
 */
class m260104_000002_add_science_packs extends Migration
{
    public function safeUp()
    {
        // Научные пакеты (resource_id 200-203)
        $this->batchInsert('resource', ['resource_id', 'name', 'type', 'icon_url', 'max_stack'], [
            [200, 'Red Science Pack', 'crafted', 'science/red_science.svg', 100],
            [201, 'Green Science Pack', 'crafted', 'science/green_science.svg', 100],
            [202, 'Blue Science Pack', 'crafted', 'science/blue_science.svg', 100],
            [203, 'Purple Science Pack', 'crafted', 'science/purple_science.svg', 100],
        ]);
    }

    public function safeDown()
    {
        $this->delete('resource', ['resource_id' => [200, 201, 202, 203]]);
    }
}
