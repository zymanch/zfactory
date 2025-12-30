<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_resource}}`.
 */
class m251230_052132_create_user_resource_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%user_resource}}', [
            'user_resource_id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'quantity' => $this->integer()->unsigned()->notNull()->defaultValue(0),
        ]);

        // Create indexes
        $this->createIndex(
            'idx_user',
            '{{%user_resource}}',
            'user_id'
        );

        $this->createIndex(
            'unique_user_resource',
            '{{%user_resource}}',
            ['user_id', 'resource_id'],
            true
        );

        // Add foreign keys
        $this->addForeignKey(
            'fk_user_resource_user',
            '{{%user_resource}}',
            'user_id',
            '{{%user}}',
            'user_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_user_resource_resource',
            '{{%user_resource}}',
            'resource_id',
            '{{%resource}}',
            'resource_id',
            'CASCADE',
            'CASCADE'
        );

        // Add starter resources for user_id=1
        $this->batchInsert('{{%user_resource}}', ['user_id', 'resource_id', 'quantity'], [
            [1, 102, 100], // 100 Iron Plate
            [1, 5, 50],    // 50 Stone
            [1, 106, 20],  // 20 Gear
            [1, 104, 50],  // 50 Copper Wire
            [1, 110, 10],  // 10 Circuit
            [1, 108, 10],  // 10 Crystal
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_user_resource_resource', '{{%user_resource}}');
        $this->dropForeignKey('fk_user_resource_user', '{{%user_resource}}');
        $this->dropTable('{{%user_resource}}');
    }
}
