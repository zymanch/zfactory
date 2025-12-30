<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%entity_type_cost}}`.
 */
class m251230_052306_create_entity_type_cost_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%entity_type_cost}}', [
            'entity_type_cost_id' => $this->primaryKey()->unsigned(),
            'entity_type_id' => $this->integer()->unsigned()->notNull(),
            'resource_id' => $this->integer()->unsigned()->notNull(),
            'quantity' => $this->integer()->unsigned()->notNull(),
        ]);

        // Create indexes
        $this->createIndex(
            'idx_entity_type',
            '{{%entity_type_cost}}',
            'entity_type_id'
        );

        $this->createIndex(
            'unique_type_resource',
            '{{%entity_type_cost}}',
            ['entity_type_id', 'resource_id'],
            true
        );

        // Add foreign keys
        $this->addForeignKey(
            'fk_entity_type_cost_entity_type',
            '{{%entity_type_cost}}',
            'entity_type_id',
            '{{%entity_type}}',
            'entity_type_id',
            'CASCADE',
            'CASCADE'
        );

        $this->addForeignKey(
            'fk_entity_type_cost_resource',
            '{{%entity_type_cost}}',
            'resource_id',
            '{{%resource}}',
            'resource_id',
            'CASCADE',
            'CASCADE'
        );

        // Add building costs
        // Format: [entity_type_id, resource_id, quantity]
        // Resources: Iron Plate=102, Stone=5, Gear=106, Copper Wire=104, Circuit=110, Crystal=108
        $this->batchInsert('{{%entity_type_cost}}', ['entity_type_id', 'resource_id', 'quantity'], [
            // Conveyor (base - id 100, right orientation)
            [100, 102, 2], // 2 Iron Plate

            // Buildings
            [101, 102, 10], // Small Furnace: 10 Iron Plate
            [101, 5, 5],    //                5 Stone

            [102, 102, 15], // Small Ore Drill: 15 Iron Plate
            [102, 106, 10], //                  10 Gear

            [103, 102, 20], // Assembly Machine: 20 Iron Plate
            [103, 106, 10], //                   10 Gear
            [103, 110, 5],  //                   5 Circuit

            [104, 102, 10], // Storage Chest: 10 Iron Plate

            [105, 102, 2],  // Power Pole: 2 Iron Plate
            [105, 104, 2],  //             2 Copper Wire

            [106, 102, 30], // Steam Engine: 30 Iron Plate
            [106, 106, 15], //               15 Gear

            [107, 102, 25], // Boiler: 25 Iron Plate
            [107, 5, 5],    //         5 Stone

            [108, 102, 20], // Medium Ore Drill: 20 Iron Plate
            [108, 106, 15], //                   15 Gear

            [506, 102, 25], // Large Ore Drill: 25 Iron Plate
            [506, 106, 15], //                  15 Gear

            // Manipulators (base types only)
            [200, 102, 5],  // Short Manipulator: 5 Iron Plate
            [200, 106, 2],  //                    2 Gear

            [201, 102, 8],  // Long Manipulator: 8 Iron Plate
            [201, 106, 3],  //                   3 Gear

            // Crystal Towers
            [400, 102, 10], // Small Crystal Tower: 10 Iron Plate
            [400, 108, 5],  //                      5 Crystal

            [401, 102, 20], // Medium Crystal Tower: 20 Iron Plate
            [401, 108, 10], //                       10 Crystal

            [402, 102, 40], // Large Crystal Tower: 40 Iron Plate
            [402, 108, 20], //                      20 Crystal

            // Sawmills
            [500, 102, 5],  // Small Sawmill: 5 Iron Plate
            [500, 106, 2],  //                2 Gear

            [501, 102, 15], // Medium Sawmill: 15 Iron Plate
            [501, 106, 10], //                 10 Gear

            [502, 102, 25], // Large Sawmill: 25 Iron Plate
            [502, 106, 15], //                 15 Gear

            // Stone Quarries
            [503, 102, 5],  // Small Stone Quarry: 5 Iron Plate
            [503, 106, 2],  //                     2 Gear

            [504, 102, 15], // Medium Stone Quarry: 15 Iron Plate
            [504, 106, 10], //                      10 Gear

            [505, 102, 25], // Large Stone Quarry: 25 Iron Plate
            [505, 106, 15], //                      15 Gear

            // Mines (silver/gold)
            [507, 102, 8],  // Small Mine: 8 Iron Plate
            [507, 106, 5],  //             5 Gear

            [508, 102, 18], // Medium Mine: 18 Iron Plate
            [508, 106, 12], //              12 Gear

            [509, 102, 25], // Large Mine: 25 Iron Plate
            [509, 106, 15], //              15 Gear

            // Quarries (aluminum/titanium)
            [510, 102, 8],  // Small Quarry: 8 Iron Plate
            [510, 106, 5],  //               5 Gear

            [511, 102, 18], // Medium Quarry: 18 Iron Plate
            [511, 106, 12], //                12 Gear

            [512, 102, 25], // Large Quarry: 25 Iron Plate
            [512, 106, 15], //                15 Gear
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk_entity_type_cost_resource', '{{%entity_type_cost}}');
        $this->dropForeignKey('fk_entity_type_cost_entity_type', '{{%entity_type_cost}}');
        $this->dropTable('{{%entity_type_cost}}');
    }
}
