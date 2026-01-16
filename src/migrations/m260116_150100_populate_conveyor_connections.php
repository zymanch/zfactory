<?php
use yii\db\Migration;

/**
 * Migration: Populate input_connections and output_connections for all conveyor types
 *
 * Logic:
 * - Regular conveyors: 1 input (opposite to orientation), 1 output (same as orientation)
 * - Splitters: 1 input (opposite), 2 outputs (perpendicular to input direction)
 * - Underground IN: 1 input only, resources go underground
 * - Underground OUT: 1 output only, resources come from underground
 */
class m260116_150100_populate_conveyor_connections extends Migration
{
    public function safeUp()
    {
        // ==================== Regular Conveyors (IDs 100, 120-122) ====================
        // Input: all sides except output direction | Output: movement direction only
        $this->update('entity_type', ['input_connections' => 'up,down,left', 'output_connections' => 'right'], ['entity_type_id' => 100]); // right
        $this->update('entity_type', ['input_connections' => 'down,left,right', 'output_connections' => 'up'], ['entity_type_id' => 120]); // up
        $this->update('entity_type', ['input_connections' => 'up,left,right', 'output_connections' => 'down'], ['entity_type_id' => 121]); // down
        $this->update('entity_type', ['input_connections' => 'up,down,right', 'output_connections' => 'left'], ['entity_type_id' => 122]); // left

        // ==================== Dual Conveyors (IDs 123-126) ====================
        $this->update('entity_type', ['input_connections' => 'up,down,left', 'output_connections' => 'right'], ['entity_type_id' => 123]); // right
        $this->update('entity_type', ['input_connections' => 'down,left,right', 'output_connections' => 'up'], ['entity_type_id' => 124]); // up
        $this->update('entity_type', ['input_connections' => 'up,down,right', 'output_connections' => 'left'], ['entity_type_id' => 125]); // left
        $this->update('entity_type', ['input_connections' => 'up,left,right', 'output_connections' => 'down'], ['entity_type_id' => 126]); // down

        // ==================== Fast Dual Conveyors (IDs 127-130) ====================
        $this->update('entity_type', ['input_connections' => 'up,down,left', 'output_connections' => 'right'], ['entity_type_id' => 127]); // right
        $this->update('entity_type', ['input_connections' => 'down,left,right', 'output_connections' => 'up'], ['entity_type_id' => 128]); // up
        $this->update('entity_type', ['input_connections' => 'up,down,right', 'output_connections' => 'left'], ['entity_type_id' => 129]); // left
        $this->update('entity_type', ['input_connections' => 'up,left,right', 'output_connections' => 'down'], ['entity_type_id' => 130]); // down

        // ==================== Splitters Normal (IDs 800-803) ====================
        // Splitter right: input from left, outputs to up and down (perpendicular)
        $this->update('entity_type', ['input_connections' => 'left', 'output_connections' => 'up,down'], ['entity_type_id' => 800]);
        // Splitter down: input from up, outputs to left and right
        $this->update('entity_type', ['input_connections' => 'up', 'output_connections' => 'left,right'], ['entity_type_id' => 801]);
        // Splitter left: input from right, outputs to down and up
        $this->update('entity_type', ['input_connections' => 'right', 'output_connections' => 'down,up'], ['entity_type_id' => 802]);
        // Splitter up: input from down, outputs to left and right
        $this->update('entity_type', ['input_connections' => 'down', 'output_connections' => 'left,right'], ['entity_type_id' => 803]);

        // ==================== Splitters Dual (IDs 804-807) ====================
        $this->update('entity_type', ['input_connections' => 'left', 'output_connections' => 'up,down'], ['entity_type_id' => 804]);
        $this->update('entity_type', ['input_connections' => 'up', 'output_connections' => 'left,right'], ['entity_type_id' => 805]);
        $this->update('entity_type', ['input_connections' => 'right', 'output_connections' => 'down,up'], ['entity_type_id' => 806]);
        $this->update('entity_type', ['input_connections' => 'down', 'output_connections' => 'left,right'], ['entity_type_id' => 807]);

        // ==================== Splitters Fast (IDs 808-811) ====================
        $this->update('entity_type', ['input_connections' => 'left', 'output_connections' => 'up,down'], ['entity_type_id' => 808]);
        $this->update('entity_type', ['input_connections' => 'up', 'output_connections' => 'left,right'], ['entity_type_id' => 809]);
        $this->update('entity_type', ['input_connections' => 'right', 'output_connections' => 'down,up'], ['entity_type_id' => 810]);
        $this->update('entity_type', ['input_connections' => 'down', 'output_connections' => 'left,right'], ['entity_type_id' => 811]);

        // ==================== Underground IN (IDs 812-815) - Only Input ====================
        $this->update('entity_type', ['input_connections' => 'left', 'output_connections' => null], ['entity_type_id' => 812]); // right
        $this->update('entity_type', ['input_connections' => 'up', 'output_connections' => null], ['entity_type_id' => 813]); // down
        $this->update('entity_type', ['input_connections' => 'right', 'output_connections' => null], ['entity_type_id' => 814]); // left
        $this->update('entity_type', ['input_connections' => 'down', 'output_connections' => null], ['entity_type_id' => 815]); // up

        // ==================== Underground OUT (IDs 816-819) - Only Output ====================
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'right'], ['entity_type_id' => 816]); // right
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'down'], ['entity_type_id' => 817]); // down
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'left'], ['entity_type_id' => 818]); // left
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'up'], ['entity_type_id' => 819]); // up

        // ==================== Underground Dual IN (IDs 820-823) ====================
        $this->update('entity_type', ['input_connections' => 'left', 'output_connections' => null], ['entity_type_id' => 820]); // right
        $this->update('entity_type', ['input_connections' => 'up', 'output_connections' => null], ['entity_type_id' => 821]); // down
        $this->update('entity_type', ['input_connections' => 'right', 'output_connections' => null], ['entity_type_id' => 822]); // left
        $this->update('entity_type', ['input_connections' => 'down', 'output_connections' => null], ['entity_type_id' => 823]); // up

        // ==================== Underground Dual OUT (IDs 824-827) ====================
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'right'], ['entity_type_id' => 824]); // right
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'down'], ['entity_type_id' => 825]); // down
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'left'], ['entity_type_id' => 826]); // left
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'up'], ['entity_type_id' => 827]); // up

        // ==================== Underground Fast IN (IDs 828-831) ====================
        $this->update('entity_type', ['input_connections' => 'left', 'output_connections' => null], ['entity_type_id' => 828]); // right
        $this->update('entity_type', ['input_connections' => 'up', 'output_connections' => null], ['entity_type_id' => 829]); // down
        $this->update('entity_type', ['input_connections' => 'right', 'output_connections' => null], ['entity_type_id' => 830]); // left
        $this->update('entity_type', ['input_connections' => 'down', 'output_connections' => null], ['entity_type_id' => 831]); // up

        // ==================== Underground Fast OUT (IDs 832-835) ====================
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'right'], ['entity_type_id' => 832]); // right
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'down'], ['entity_type_id' => 833]); // down
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'left'], ['entity_type_id' => 834]); // left
        $this->update('entity_type', ['input_connections' => null, 'output_connections' => 'up'], ['entity_type_id' => 835]); // up
    }

    public function safeDown()
    {
        // Clear all connection data for conveyor types
        $this->update('entity_type',
            ['input_connections' => null, 'output_connections' => null],
            ['type' => 'conveyor']
        );
    }
}
