<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%ship_entity}}`.
 */
class m251230_123132_create_ship_entity_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%ship_entity}}', [
            'ship_entity_id' => $this->primaryKey()->unsigned(),
            'user_id' => $this->integer()->unsigned()->notNull(),
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
            'x' => $this->integer()->notNull()->comment('Ship-relative X coordinate (offset from ship_attach_x)'),
            'y' => $this->integer()->notNull()->comment('Ship-relative Y coordinate (offset from ship_attach_y)'),
            'state' => "ENUM('built', 'blueprint') NOT NULL DEFAULT 'blueprint'",
            'durability' => $this->integer()->unsigned()->notNull()->defaultValue(0),
        ]);

        $this->createIndex(
            'idx_ship_entity_user',
            '{{%ship_entity}}',
            'user_id'
        );

        $this->createIndex(
            'idx_ship_entity_coords',
            '{{%ship_entity}}',
            ['user_id', 'x', 'y']
        );

        $this->addForeignKey(
            'fk_ship_entity_user',
            '{{%ship_entity}}',
            'user_id',
            '{{%user}}',
            'user_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_ship_entity_type',
            '{{%ship_entity}}',
            'entity_type_id',
            '{{%entity_type}}',
            'entity_type_id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%ship_entity}}');
    }
}
