<?php

use yii\db\Migration;

/**
 * Adds animation_fps column to entity_type table
 *
 * For conveyors: calculated so that 1 animation cycle = time for resource to travel 1 tile
 * - Conveyor has 8 animation frames
 * - Resource travels 1 tile (64px) in ~2 seconds at standard speed
 * - FPS = 8 frames / 2 seconds = 4 FPS
 */
class m251226_133842_add_animation_fps_to_entity_type extends Migration
{
    public function safeUp()
    {
        // Add animation_fps column (frames per second)
        $this->addColumn('entity_type', 'animation_fps', $this->decimal(5, 2)->null()->comment('Animation speed in frames per second. NULL = no animation'));

        // Set FPS for conveyors (all 4 orientations)
        // 4 FPS = 8 frames complete in 2 seconds (time for resource to travel 1 tile)
        $this->update('entity_type', ['animation_fps' => 4.0], ['type' => 'transporter']);
    }

    public function safeDown()
    {
        $this->dropColumn('entity_type', 'animation_fps');
    }
}
