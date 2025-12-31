<?php

use yii\db\Migration;

/**
 * Add variations_count column to landing table
 */
class m251223_141000_add_variations_count_to_landing extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%landing}}', 'variations_count', $this->integer()->notNull()->defaultValue(5)->after('folder'));
    }

    public function safeDown()
    {
        $this->dropColumn('{{%landing}}', 'variations_count');
    }
}
