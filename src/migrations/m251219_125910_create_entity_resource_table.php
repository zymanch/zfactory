<?php

use yii\db\Migration;

/**
 * Creates table `entity_resource` - links entities to resources they contain
 */
class m251219_125910_create_entity_resource_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('entity_resource', [
            'entity_resource_id' => $this->primaryKey()->unsigned(),
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'amount' => $this->integer()->unsigned()->notNull()->defaultValue(0),
        ]);

        // Indexes
        $this->createIndex('idx_entity_resource_entity', 'entity_resource', 'entity_id');
        $this->createIndex('idx_entity_resource_resource', 'entity_resource', 'resource_id');
        $this->createIndex('idx_entity_resource_unique', 'entity_resource', ['entity_id', 'resource_id'], true);

        // Foreign keys
        $this->addForeignKey(
            'fk_entity_resource_entity',
            'entity_resource',
            'entity_id',
            'entity',
            'entity_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_entity_resource_resource',
            'entity_resource',
            'resource_id',
            'resource',
            'resource_id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_entity_resource_resource', 'entity_resource');
        $this->dropForeignKey('fk_entity_resource_entity', 'entity_resource');
        $this->dropTable('entity_resource');
    }
}
