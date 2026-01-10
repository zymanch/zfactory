<?php

use yii\db\Migration;

/**
 * Create electricity_system and electricity_system_member tables
 */
class m260110_000002_create_electricity_system_tables extends Migration
{
    public function safeUp()
    {
        // Drop tables if they exist (cleanup from failed migration)
        $this->execute('DROP TABLE IF EXISTS {{%electricity_system_member}}');
        $this->execute('DROP TABLE IF EXISTS {{%electricity_system}}');

        // Create electricity_system table
        $this->createTable('{{%electricity_system}}', [
            'system_id' => $this->primaryKey(),
            'region_id' => $this->integer()->unsigned()->notNull(),
            'total_capacity' => $this->integer()->unsigned()->defaultValue(0)->comment('Sum of all battery capacities in system'),
            'total_electricity' => $this->integer()->unsigned()->defaultValue(0)->comment('Current electricity stored in system'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // Add foreign key to region
        $this->addForeignKey(
            'fk-electricity_system-region_id',
            '{{%electricity_system}}',
            'region_id',
            '{{%region}}',
            'region_id',
            'CASCADE',
            'CASCADE'
        );

        // Add index on region_id for faster lookups
        $this->createIndex(
            'idx-electricity_system-region_id',
            '{{%electricity_system}}',
            'region_id'
        );

        // Create electricity_system_member table
        $this->createTable('{{%electricity_system_member}}', [
            'member_id' => $this->primaryKey(),
            'system_id' => $this->integer()->notNull(),
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'role' => "ENUM('pylon', 'battery', 'generator', 'consumer') NOT NULL",
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Add foreign key to electricity_system
        $this->addForeignKey(
            'fk-electricity_system_member-system_id',
            '{{%electricity_system_member}}',
            'system_id',
            '{{%electricity_system}}',
            'system_id',
            'CASCADE',
            'CASCADE'
        );

        // Add foreign key to entity
        $this->addForeignKey(
            'fk-electricity_system_member-entity_id',
            '{{%electricity_system_member}}',
            'entity_id',
            '{{%entity}}',
            'entity_id',
            'CASCADE',
            'CASCADE'
        );

        // Add indexes
        $this->createIndex(
            'idx-electricity_system_member-system_id',
            '{{%electricity_system_member}}',
            'system_id'
        );

        $this->createIndex(
            'idx-electricity_system_member-entity_id',
            '{{%electricity_system_member}}',
            'entity_id',
            true // Unique: each entity can only be in one system
        );
    }

    public function safeDown()
    {
        // Drop foreign keys and indexes
        $this->dropForeignKey('fk-electricity_system_member-entity_id', '{{%electricity_system_member}}');
        $this->dropForeignKey('fk-electricity_system_member-system_id', '{{%electricity_system_member}}');
        $this->dropIndex('idx-electricity_system_member-entity_id', '{{%electricity_system_member}}');
        $this->dropIndex('idx-electricity_system_member-system_id', '{{%electricity_system_member}}');

        $this->dropTable('{{%electricity_system_member}}');

        $this->dropForeignKey('fk-electricity_system-region_id', '{{%electricity_system}}');
        $this->dropIndex('idx-electricity_system-region_id', '{{%electricity_system}}');

        $this->dropTable('{{%electricity_system}}');
    }
}
