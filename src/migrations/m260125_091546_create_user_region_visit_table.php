<?php

use yii\db\Migration;

/**
 * Create user_region_visit table
 * Tracks user visits to regions with view radius history
 */
class m260125_091546_create_user_region_visit_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Drop table if exists (from failed migration)
        $this->execute('DROP TABLE IF EXISTS user_region_visit');

        $this->createTable('user_region_visit', [
            'user_region_visit_id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull()->unsigned(),
            'region_id' => $this->integer()->notNull()->unsigned(),
            'from_region_id' => $this->integer()->null()->unsigned(),
            'view_radius' => $this->integer()->notNull()->defaultValue(5),
            'last_visit_at' => $this->timestamp()->null()->defaultExpression('CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        // Unique constraint: one record per user per region
        $this->createIndex(
            'idx-user_region_visit-user_id-region_id',
            'user_region_visit',
            ['user_id', 'region_id'],
            true
        );

        // Foreign keys
        $this->addForeignKey(
            'fk-user_region_visit-user_id',
            'user_region_visit',
            'user_id',
            'user',
            'user_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-user_region_visit-region_id',
            'user_region_visit',
            'region_id',
            'region',
            'region_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk-user_region_visit-from_region_id',
            'user_region_visit',
            'from_region_id',
            'region',
            'region_id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-user_region_visit-from_region_id', 'user_region_visit');
        $this->dropForeignKey('fk-user_region_visit-region_id', 'user_region_visit');
        $this->dropForeignKey('fk-user_region_visit-user_id', 'user_region_visit');

        $this->dropTable('user_region_visit');
    }
}
