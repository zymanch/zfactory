<?php

use yii\db\Migration;

/**
 * Обновление entity_type: extension='png' и icon_url на normal.png
 */
class m251226_090500_update_entity_icons_to_png extends Migration
{
    public function safeUp()
    {
        // Обновляем extension на 'png' для всех записей
        $this->update('entity_type', ['extension' => 'png'], ['extension' => 'svg']);

        // Обновляем icon_url: заменяем icon.svg на normal.png
        $this->execute("UPDATE entity_type SET icon_url = REPLACE(icon_url, 'icon.svg', 'normal.png') WHERE icon_url LIKE '%icon.svg'");

        echo "Updated entity_type: extension='png', icon_url uses normal.png\n";
    }

    public function safeDown()
    {
        // Откатываем изменения
        $this->update('entity_type', ['extension' => 'svg'], ['extension' => 'png']);
        $this->execute("UPDATE entity_type SET icon_url = REPLACE(icon_url, 'normal.png', 'icon.svg') WHERE icon_url LIKE '%normal.png'");

        echo "Reverted entity_type to svg and icon.svg\n";
    }
}
