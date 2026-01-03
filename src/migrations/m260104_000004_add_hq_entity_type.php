<?php

use yii\db\Migration;

/**
 * Добавление HQ (Headquarters) - главное здание для исследований
 */
class m260104_000004_add_hq_entity_type extends Migration
{
    public function safeUp()
    {
        // Добавляем новый тип 'hq' в ENUM
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage','hq') NOT NULL");

        // Создаём HQ entity type
        $this->insert('entity_type', [
            'entity_type_id' => 999,
            'type' => 'hq',
            'name' => 'Headquarters',
            'image_url' => 'hq',
            'extension' => 'svg',
            'max_durability' => 10000,
            'width' => 3,
            'height' => 3,
            'icon_url' => 'hq/icon.svg',
            'power' => 10, // Даёт небольшой радиус видимости
            'parent_entity_type_id' => null,
            'orientation' => 'none',
            'description' => 'Main building for research and technology. Click to open the technology tree.',
            'construction_ticks' => 0, // Мгновенное строительство
        ]);
    }

    public function safeDown()
    {
        $this->delete('entity_type', ['entity_type_id' => 999]);

        // Возвращаем старый ENUM
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage') NOT NULL");
    }
}
