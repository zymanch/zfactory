<?php

use yii\db\Migration;

/**
 * Create landing_adjacency table
 * Stores which landing types can naturally border each other
 */
class m251222_100000_create_landing_adjacency extends Migration
{
    public function safeUp()
    {
        $this->createTable('landing_adjacency', [
            'adjacency_id' => $this->primaryKey()->unsigned(),
            'landing_id_1' => $this->integer()->unsigned()->notNull(),
            'landing_id_2' => $this->integer()->unsigned()->notNull(),
        ], 'ENGINE=InnoDB DEFAULT CHARSET=utf8');

        // Unique constraint on pair
        $this->createIndex(
            'idx_unique_pair',
            'landing_adjacency',
            ['landing_id_1', 'landing_id_2'],
            true
        );

        $this->addForeignKey(
            'fk_landing_adjacency_1',
            'landing_adjacency',
            'landing_id_1',
            'landing',
            'landing_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_landing_adjacency_2',
            'landing_adjacency',
            'landing_id_2',
            'landing',
            'landing_id',
            'CASCADE',
            'CASCADE'
        );

        // Insert natural transition pairs (max 3 per type)
        // grass (1): dirt, sand, swamp
        $this->insert('landing_adjacency', ['landing_id_1' => 1, 'landing_id_2' => 2]); // grass-dirt
        $this->insert('landing_adjacency', ['landing_id_1' => 1, 'landing_id_2' => 3]); // grass-sand
        $this->insert('landing_adjacency', ['landing_id_1' => 1, 'landing_id_2' => 8]); // grass-swamp

        // dirt (2): grass (already), sand, stone
        $this->insert('landing_adjacency', ['landing_id_1' => 2, 'landing_id_2' => 3]); // dirt-sand
        $this->insert('landing_adjacency', ['landing_id_1' => 2, 'landing_id_2' => 5]); // dirt-stone

        // sand (3): grass (already), dirt (already), water
        $this->insert('landing_adjacency', ['landing_id_1' => 3, 'landing_id_2' => 4]); // sand-water

        // water (4): sand (already), swamp, lava
        $this->insert('landing_adjacency', ['landing_id_1' => 4, 'landing_id_2' => 6]); // water-lava
        $this->insert('landing_adjacency', ['landing_id_1' => 4, 'landing_id_2' => 8]); // water-swamp

        // stone (5): dirt (already), snow, lava
        $this->insert('landing_adjacency', ['landing_id_1' => 5, 'landing_id_2' => 6]); // stone-lava
        $this->insert('landing_adjacency', ['landing_id_1' => 5, 'landing_id_2' => 7]); // stone-snow

        // snow (7): stone (already), grass
        $this->insert('landing_adjacency', ['landing_id_1' => 1, 'landing_id_2' => 7]); // grass-snow
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_landing_adjacency_2', 'landing_adjacency');
        $this->dropForeignKey('fk_landing_adjacency_1', 'landing_adjacency');
        $this->dropTable('landing_adjacency');
    }
}
