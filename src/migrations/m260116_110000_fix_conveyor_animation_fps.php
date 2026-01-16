<?php

use yii\db\Migration;

/**
 * Fix animation_fps for conveyors to sync with resource movement
 *
 * Synchronization formula: animation_fps = 16 * (power / 100)
 *
 * Logic:
 * - 8 animation frames should complete exactly when resource travels 1 tile
 * - At power=100: resource travels 30 ticks = 0.5 sec @ 60FPS
 * - animation_fps = 8 frames / 0.5 sec = 16 FPS
 * - At power=200: resource travels 15 ticks = 0.25 sec
 * - animation_fps = 8 frames / 0.25 sec = 32 FPS
 */
class m260116_110000_fix_conveyor_animation_fps extends Migration
{
    public function safeUp()
    {
        // Normal conveyors (power=100) → 16 FPS
        $this->update('entity_type', ['animation_fps' => 16], [
            'and',
            ['type' => 'conveyor'],
            ['power' => 100]
        ]);

        // Fast conveyors (power=200) → 32 FPS
        $this->update('entity_type', ['animation_fps' => 32], [
            'and',
            ['type' => 'conveyor'],
            ['power' => 200]
        ]);

        echo "Updated animation_fps for all conveyors\n";
        echo "  - power=100 → animation_fps=16 (was 4)\n";
        echo "  - power=200 → animation_fps=32 (was 8)\n";
    }

    public function safeDown()
    {
        // Revert to old values (though these were incorrect)
        $this->update('entity_type', ['animation_fps' => 4], [
            'and',
            ['type' => 'conveyor'],
            ['power' => 100]
        ]);

        $this->update('entity_type', ['animation_fps' => 8], [
            'and',
            ['type' => 'conveyor'],
            ['power' => 200]
        ]);

        echo "Reverted animation_fps to old (incorrect) values\n";
    }
}
