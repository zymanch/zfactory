<?php

use yii\db\Migration;

/**
 * Class m260104_000007_add_dual_lane_conveyors
 *
 * Adds 8 new conveyor entity types:
 * - Type 2 (IDs 123-126): Dual-lane conveyors, power=100, blue color
 * - Type 3 (IDs 127-130): Fast dual-lane conveyors, power=200, green color
 */
class m260104_000007_add_dual_lane_conveyors extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $baseConveyors = [
            100 => ['name' => 'Conveyor Belt', 'orientation' => 'right'],
            120 => ['name' => 'Conveyor Belt', 'orientation' => 'down'],
            121 => ['name' => 'Conveyor Belt', 'orientation' => 'left'],
            122 => ['name' => 'Conveyor Belt', 'orientation' => 'up'],
        ];

        // Type 2: Dual-lane conveyors (power=100, blue)
        $type2Ids = [123, 124, 125, 126];
        foreach ($type2Ids as $i => $newId) {
            $baseId = array_keys($baseConveyors)[$i];
            $baseData = $baseConveyors[$baseId];

            $this->insert('{{%entity_type}}', [
                'entity_type_id' => $newId,
                'type' => 'transporter',
                'name' => $baseData['name'] . ' (Dual-Lane)',
                'image_url' => 'conveyor_dual',
                'extension' => 'png',
                'max_durability' => 100,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'conveyor_dual/normal.png',
                'power' => 100,
                'parent_entity_type_id' => $baseId,
                'orientation' => $baseData['orientation'],
                'animation_fps' => 4.00,
                'center_position_px' => 32,
                'description' => 'Двухполосная транспортная лента для перемещения ресурсов',
                'construction_ticks' => 60,
            ]);
        }

        // Type 3: Fast dual-lane conveyors (power=200, green)
        $type3Ids = [127, 128, 129, 130];
        foreach ($type3Ids as $i => $newId) {
            $baseId = array_keys($baseConveyors)[$i];
            $baseData = $baseConveyors[$baseId];

            $this->insert('{{%entity_type}}', [
                'entity_type_id' => $newId,
                'type' => 'transporter',
                'name' => $baseData['name'] . ' (Fast Dual-Lane)',
                'image_url' => 'conveyor_fast_dual',
                'extension' => 'png',
                'max_durability' => 100,
                'width' => 1,
                'height' => 1,
                'icon_url' => 'conveyor_fast_dual/normal.png',
                'power' => 200,
                'parent_entity_type_id' => $baseId,
                'orientation' => $baseData['orientation'],
                'animation_fps' => 8.00,
                'center_position_px' => 32,
                'description' => 'Быстрая двухполосная транспортная лента для перемещения ресурсов',
                'construction_ticks' => 60,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%entity_type}}', ['IN', 'entity_type_id', [
            123, 124, 125, 126,  // Type 2
            127, 128, 129, 130,  // Type 3
        ]]);
    }
}
