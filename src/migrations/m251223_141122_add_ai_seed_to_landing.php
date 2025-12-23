<?php

use yii\db\Migration;

/**
 * Handles adding columns to table `{{%landing}}`.
 */
class m251223_141122_add_ai_seed_to_landing extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%landing}}', 'ai_seed', $this->bigInteger()->null()->after('variations_count'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%landing}}', 'ai_seed');
    }
}
