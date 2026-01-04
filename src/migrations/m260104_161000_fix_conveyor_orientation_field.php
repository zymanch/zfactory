<?php

use yii\db\Migration;

/**
 * Fix orientation field for dual-lane and fast dual-lane conveyors
 * Orientation should match the image_url suffix
 */
class m260104_161000_fix_conveyor_orientation_field extends Migration
{
    public function safeUp()
    {
        // Fix Dual-Lane Conveyor orientations
        $this->update('entity_type', ['orientation' => 'up'], ['entity_type_id' => 124]);
        $this->update('entity_type', ['orientation' => 'down'], ['entity_type_id' => 126]);

        // Fix Fast Dual-Lane Conveyor orientations
        $this->update('entity_type', ['orientation' => 'up'], ['entity_type_id' => 128]);
        $this->update('entity_type', ['orientation' => 'down'], ['entity_type_id' => 130]);

        echo "    > Fixed conveyor orientation field\n";
    }

    public function safeDown()
    {
        // Revert to old orientations
        $this->update('entity_type', ['orientation' => 'down'], ['entity_type_id' => 124]);
        $this->update('entity_type', ['orientation' => 'up'], ['entity_type_id' => 126]);
        $this->update('entity_type', ['orientation' => 'down'], ['entity_type_id' => 128]);
        $this->update('entity_type', ['orientation' => 'up'], ['entity_type_id' => 130]);

        echo "    > Reverted conveyor orientation field\n";
    }
}
