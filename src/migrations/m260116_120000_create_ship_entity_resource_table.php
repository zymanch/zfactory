<?php

use yii\db\Migration;

/**
 * Create ship_entity_resource table
 * Mirrors entity_resource structure but for ship entities
 */
class m260116_120000_create_ship_entity_resource_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ship_entity_resource}}', [
            'ship_entity_resource_id' => $this->primaryKey()->unsigned(),
            'ship_entity_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'amount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'status' => "ENUM('empty','carrying','waiting_transfer','idle','picking','placing') NULL COMMENT 'Transport status'",
            'position_px' => $this->integer()->null()->comment('Resource position on conveyor in pixels (0-63)'),
            'from_direction' => "ENUM('up','down','left','right') NULL COMMENT 'Direction resource came from'",
            'last_output_direction' => "ENUM('left','right') NULL COMMENT 'Last output direction for splitters'",
        ]);

        // Index on ship_entity_id for fast lookups
        $this->createIndex(
            'idx_ship_entity_resource_entity',
            '{{%ship_entity_resource}}',
            'ship_entity_id'
        );

        // Index on resource_id
        $this->createIndex(
            'idx_ship_entity_resource_resource',
            '{{%ship_entity_resource}}',
            'resource_id'
        );

        // Unique constraint for building storage (position_px IS NULL)
        // Conveyors can have multiple resources at different positions
        $this->createIndex(
            'idx_ship_entity_resource_unique',
            '{{%ship_entity_resource}}',
            ['ship_entity_id', 'resource_id', 'position_px'],
            true
        );

        // Foreign key to ship_entity
        $this->addForeignKey(
            'fk_ship_entity_resource_entity',
            '{{%ship_entity_resource}}',
            'ship_entity_id',
            '{{%ship_entity}}',
            'ship_entity_id',
            'CASCADE',
            'CASCADE'
        );

        // Foreign key to resource
        $this->addForeignKey(
            'fk_ship_entity_resource_resource',
            '{{%ship_entity_resource}}',
            'resource_id',
            '{{%resource}}',
            'resource_id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%ship_entity_resource}}');
    }
}
