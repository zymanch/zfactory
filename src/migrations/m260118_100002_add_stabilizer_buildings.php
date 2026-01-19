<?php

use yii\db\Migration;

/**
 * Adds 3 stabilizer building types (entity_type_id: 950, 951, 952)
 */
class m260118_100002_add_stabilizer_buildings extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Small Stabilizer (950): 1x1, radius 5 tiles, consumes Stability resource
        $this->insert('{{%entity_type}}', [
            'entity_type_id' => 950,
            'type' => 'building',
            'subtype' => 'stabilizer',
            'name' => 'Small Stabilizer',
            'folder' => 'stabilizer_small',
            'width' => 1,
            'height' => 1,
            'power' => 5,  // Stabilization radius in tiles
            'max_durability' => 200,
            'construction_ticks' => 300,  // 5 seconds at 60fps
        ]);

        // Small Stabilizer costs: 30 Iron Plate, 10 Circuit, 5 Crystal
        $this->batchInsert('{{%entity_type_cost}}', ['entity_type_id', 'resource_id', 'quantity'], [
            [950, 102, 30],  // Iron Plate
            [950, 110, 10],  // Circuit
            [950, 108, 5],   // Crystal
        ]);

        // Medium Stabilizer (951): 2x2, radius 10 tiles, consumes electricity only
        $this->insert('{{%entity_type}}', [
            'entity_type_id' => 951,
            'type' => 'building',
            'subtype' => 'stabilizer',
            'name' => 'Medium Stabilizer',
            'folder' => 'stabilizer_medium',
            'width' => 2,
            'height' => 2,
            'power' => 10,  // Stabilization radius in tiles
            'max_durability' => 400,
            'construction_ticks' => 450,  // 7.5 seconds at 60fps
        ]);

        // Medium Stabilizer costs: 50 Steel Plate, 20 Circuit, 10 Motor
        $this->batchInsert('{{%entity_type_cost}}', ['entity_type_id', 'resource_id', 'quantity'], [
            [951, 109, 50],  // Steel Plate
            [951, 110, 20],  // Circuit
            [951, 111, 10],  // Motor
        ]);

        // Large Stabilizer (952): 3x3, radius 20 tiles, consumes Stability resource
        $this->insert('{{%entity_type}}', [
            'entity_type_id' => 952,
            'type' => 'building',
            'subtype' => 'stabilizer',
            'name' => 'Large Stabilizer',
            'folder' => 'stabilizer_large',
            'width' => 3,
            'height' => 3,
            'power' => 20,  // Stabilization radius in tiles
            'max_durability' => 600,
            'construction_ticks' => 600,  // 10 seconds at 60fps
        ]);

        // Large Stabilizer costs: 100 Steel Plate, 40 Circuit, 20 Motor, 10 Crystal
        $this->batchInsert('{{%entity_type_cost}}', ['entity_type_id', 'resource_id', 'quantity'], [
            [952, 109, 100],  // Steel Plate
            [952, 110, 40],   // Circuit
            [952, 111, 20],   // Motor
            [952, 108, 10],   // Crystal
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%entity_type_cost}}', ['entity_type_id' => [950, 951, 952]]);
        $this->delete('{{%entity_type}}', ['entity_type_id' => [950, 951, 952]]);
    }
}
