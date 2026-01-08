<?php

use yii\db\Migration;

/**
 * Creates pipe_system and pipe_system_member tables for fluid transport system
 */
class m260108_090000_create_pipe_system_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create pipe_system table
        $this->createTable('pipe_system', [
            'pipe_system_id' => $this->primaryKey()->unsigned(),
            'region_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->null()->comment('Fluid type (NULL = empty)'),
            'current_amount' => $this->integer()->unsigned()->defaultValue(0)->comment('Current fluid amount'),
            'max_capacity' => $this->integer()->unsigned()->defaultValue(0)->comment('Total capacity of all pipes'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->createIndex('idx_region', 'pipe_system', 'region_id');

        // Create pipe_system_member table
        $this->createTable('pipe_system_member', [
            'pipe_system_id' => $this->integer()->unsigned()->notNull(),
            'entity_id' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addPrimaryKey('pk_pipe_system_member', 'pipe_system_member', ['pipe_system_id', 'entity_id']);
        $this->createIndex('idx_entity', 'pipe_system_member', 'entity_id');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('pipe_system_member');
        $this->dropTable('pipe_system');
    }
}
