<?php

use yii\db\Migration;

/**
 * Drop entity_transport table
 * Transport functionality has been merged into entity_resource
 */
class m251227_140759_drop_entity_transport_table extends Migration
{
    public function safeUp()
    {
        $this->dropTable('{{%entity_transport}}');
    }

    public function safeDown()
    {
        // Recreate table structure (without data - use previous migration's down to restore data)
        $this->createTable('{{%entity_transport}}', [
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->null(),
            'amount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'position' => $this->decimal(5, 4)->notNull()->defaultValue(0),
            'lateral_offset' => $this->decimal(5, 4)->notNull()->defaultValue(0),
            'arm_position' => $this->decimal(5, 4)->notNull()->defaultValue(0.5),
            'status' => "ENUM('empty','carrying','waiting_transfer','idle','picking','placing') NOT NULL DEFAULT 'empty'",
            'PRIMARY KEY (entity_id)',
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addForeignKey(
            'fk_entity_transport_entity',
            'entity_transport',
            'entity_id',
            'entity',
            'entity_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_entity_transport_resource',
            'entity_transport',
            'resource_id',
            'resource',
            'resource_id',
            'SET NULL',
            'CASCADE'
        );
    }
}
