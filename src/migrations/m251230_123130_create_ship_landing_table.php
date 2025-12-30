<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ship_landing}}`.
 */
class m251230_123130_create_ship_landing_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%ship_landing}}', [
            'ship_landing_id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'landing_id' => $this->integer()->unsigned()->notNull(),
            'x' => $this->integer()->notNull()->comment('Ship-relative X coordinate (offset from ship_attach_x)'),
            'y' => $this->integer()->notNull()->comment('Ship-relative Y coordinate (offset from ship_attach_y)'),
            'variation' => $this->integer()->unsigned()->notNull()->defaultValue(1),
        ]);

        $this->createIndex(
            'idx_ship_landing_user',
            '{{%ship_landing}}',
            'user_id'
        );

        $this->createIndex(
            'idx_ship_landing_coords',
            '{{%ship_landing}}',
            ['user_id', 'x', 'y'],
            true
        );

        $this->addForeignKey(
            'fk_ship_landing_user',
            '{{%ship_landing}}',
            'user_id',
            '{{%user}}',
            'user_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_ship_landing_landing',
            '{{%ship_landing}}',
            'landing_id',
            '{{%landing}}',
            'landing_id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%ship_landing}}');
    }
}
