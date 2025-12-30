<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_region_visit}}`.
 */
class m251230_081743_create_user_region_visit_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%user_region_visit}}', [
            'user_region_visit_id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'region_id' => $this->integer()->unsigned()->notNull(),
            'view_radius' => $this->integer()->unsigned()->notNull(),
            'last_visit_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create unique constraint
        $this->createIndex('unique_user_region', '{{%user_region_visit}}', ['user_id', 'region_id'], true);

        // Create foreign keys
        $this->addForeignKey(
            'fk_user_region_visit_user',
            '{{%user_region_visit}}',
            'user_id',
            '{{%user}}',
            'user_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_user_region_visit_region',
            '{{%user_region_visit}}',
            'region_id',
            '{{%region}}',
            'region_id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%user_region_visit}}');
    }
}
