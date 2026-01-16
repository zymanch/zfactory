<?php
use yii\db\Migration;

/**
 * Migration: Fix input_connections for regular conveyors
 *
 * Conveyors can receive resources from 3 sides (all except output direction)
 * and output only to 1 side (movement direction).
 */
class m260116_150200_fix_conveyor_input_connections extends Migration
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
    }

    public function safeDown()
    {
        // Revert to single-direction input (incorrect logic)
        $this->update('entity_type', ['input_connections' => 'left'], ['entity_type_id' => 100]);
        $this->update('entity_type', ['input_connections' => 'down'], ['entity_type_id' => 120]);
        $this->update('entity_type', ['input_connections' => 'up'], ['entity_type_id' => 121]);
        $this->update('entity_type', ['input_connections' => 'right'], ['entity_type_id' => 122]);

        $this->update('entity_type', ['input_connections' => 'left'], ['entity_type_id' => 123]);
        $this->update('entity_type', ['input_connections' => 'down'], ['entity_type_id' => 124]);
        $this->update('entity_type', ['input_connections' => 'right'], ['entity_type_id' => 125]);
        $this->update('entity_type', ['input_connections' => 'up'], ['entity_type_id' => 126]);

        $this->update('entity_type', ['input_connections' => 'left'], ['entity_type_id' => 127]);
        $this->update('entity_type', ['input_connections' => 'down'], ['entity_type_id' => 128]);
        $this->update('entity_type', ['input_connections' => 'right'], ['entity_type_id' => 129]);
        $this->update('entity_type', ['input_connections' => 'up'], ['entity_type_id' => 130]);
    }
}
