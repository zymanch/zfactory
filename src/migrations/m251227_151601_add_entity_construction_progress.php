<?php

use yii\db\Migration;

/**
 * Добавляет поле construction_progress в таблицу entity
 */
class m251227_151601_add_entity_construction_progress extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('entity', 'construction_progress', $this->tinyInteger()->unsigned()->notNull()->defaultValue(100)->comment('Прогресс строительства 0-100%'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('entity', 'construction_progress');
    }
}
