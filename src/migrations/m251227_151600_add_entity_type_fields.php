<?php

use yii\db\Migration;

/**
 * Добавляет поля description и construction_ticks в таблицу entity_type
 */
class m251227_151600_add_entity_type_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('entity_type', 'description', $this->text()->null()->comment('Описание entity на русском языке'));
        $this->addColumn('entity_type', 'construction_ticks', $this->integer()->notNull()->defaultValue(60)->comment('Количество тиков для строительства'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('entity_type', 'construction_ticks');
        $this->dropColumn('entity_type', 'description');
    }
}
