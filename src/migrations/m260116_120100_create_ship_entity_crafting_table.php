<?php

use yii\db\Migration;

/**
 * Create ship_entity_crafting table
 * Mirrors entity_crafting structure but for ship entities
 */
class m260116_120100_create_ship_entity_crafting_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%ship_entity_crafting}}', [
            'ship_entity_crafting_id' => $this->primaryKey()->unsigned(),
            'ship_entity_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
            'ticks_remaining' => $this->integer()->unsigned()->notNull()->defaultValue(0)->comment('Ticks until craft completes'),
        ]);

        // Index on ship_entity_id (typically 1:1 relationship)
        $this->createIndex(
            'idx_ship_entity_crafting_entity',
            '{{%ship_entity_crafting}}',
            'ship_entity_id',
            true  // Unique - one crafting per entity
        );

        // Index on recipe_id
        $this->createIndex(
            'idx_ship_entity_crafting_recipe',
            '{{%ship_entity_crafting}}',
            'recipe_id'
        );

        // Foreign key to ship_entity
        $this->addForeignKey(
            'fk_ship_entity_crafting_entity',
            '{{%ship_entity_crafting}}',
            'ship_entity_id',
            '{{%ship_entity}}',
            'ship_entity_id',
            'CASCADE',
            'CASCADE'
        );

        // Foreign key to recipe
        $this->addForeignKey(
            'fk_ship_entity_crafting_recipe',
            '{{%ship_entity_crafting}}',
            'recipe_id',
            '{{%recipe}}',
            'recipe_id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%ship_entity_crafting}}');
    }
}
