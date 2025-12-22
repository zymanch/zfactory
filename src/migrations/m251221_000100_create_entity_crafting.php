<?php

use yii\db\Migration;

/**
 * Create entity_crafting table
 * Stores active crafting processes for buildings
 */
class m251221_000100_create_entity_crafting extends Migration
{
    public function safeUp()
    {
        $this->createTable('entity_crafting', [
            'entity_id' => $this->integer()->unsigned()->notNull(),
            'recipe_id' => $this->integer()->unsigned()->notNull(),
            'ticks_remaining' => $this->integer()->unsigned()->notNull(),
            'PRIMARY KEY (entity_id)',
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        $this->addForeignKey(
            'fk_entity_crafting_entity',
            'entity_crafting',
            'entity_id',
            'entity',
            'entity_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_entity_crafting_recipe',
            'entity_crafting',
            'recipe_id',
            'recipe',
            'recipe_id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_entity_crafting_recipe', 'entity_crafting');
        $this->dropForeignKey('fk_entity_crafting_entity', 'entity_crafting');
        $this->dropTable('entity_crafting');
    }
}
