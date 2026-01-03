<?php

use yii\db\Migration;

/**
 * Обновление существующего HQ (entity_type_id=0) для системы исследований
 */
class m260104_000004_add_hq_entity_type extends Migration
{
    public function safeUp()
    {
        // Добавляем новый тип 'hq' в ENUM
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage','hq') NOT NULL");

        // Обновляем существующий HQ (entity_type_id=0) на тип 'hq'
        $this->update('entity_type', [
            'type' => 'hq',
            'description' => 'Main building for research and technology. Click to open the technology tree.',
        ], ['entity_type_id' => 0]);
    }

    public function safeDown()
    {
        // Возвращаем тип обратно на 'building'
        $this->update('entity_type', [
            'type' => 'building',
            'description' => null,
        ], ['entity_type_id' => 0]);

        // Возвращаем старый ENUM
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage') NOT NULL");
    }
}
