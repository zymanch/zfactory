<?php

use yii\db\Migration;

/**
 * Fix conveyor orientation structure
 * - Dual-lane and fast dual-lane conveyors should not be orientation variants of basic conveyor
 * - Each conveyor type should have its own orientation variants
 * - Fix image_url for orientation variants
 */
class m260104_160000_fix_conveyor_orientation_structure extends Migration
{
    public function safeUp()
    {
        // Fix Dual-Lane Conveyor (123-126)
        // 123 should be base (no parent)
        $this->update('entity_type', ['parent_entity_type_id' => null], ['entity_type_id' => 123]);

        // 124-126 should have parent=123 and correct image_url
        $this->update('entity_type', [
            'parent_entity_type_id' => 123,
            'image_url' => 'conveyor_dual_up'
        ], ['entity_type_id' => 124]);

        $this->update('entity_type', [
            'parent_entity_type_id' => 123,
            'image_url' => 'conveyor_dual_left'
        ], ['entity_type_id' => 125]);

        $this->update('entity_type', [
            'parent_entity_type_id' => 123,
            'image_url' => 'conveyor_dual_down'
        ], ['entity_type_id' => 126]);

        // Fix Fast Dual-Lane Conveyor (127-130)
        // 127 should be base (no parent)
        $this->update('entity_type', ['parent_entity_type_id' => null], ['entity_type_id' => 127]);

        // 128-130 should have parent=127 and correct image_url
        $this->update('entity_type', [
            'parent_entity_type_id' => 127,
            'image_url' => 'conveyor_fast_dual_up'
        ], ['entity_type_id' => 128]);

        $this->update('entity_type', [
            'parent_entity_type_id' => 127,
            'image_url' => 'conveyor_fast_dual_left'
        ], ['entity_type_id' => 129]);

        $this->update('entity_type', [
            'parent_entity_type_id' => 127,
            'image_url' => 'conveyor_fast_dual_down'
        ], ['entity_type_id' => 130]);

        echo "    > Fixed conveyor orientation structure\n";
    }

    public function safeDown()
    {
        // Revert changes
        $this->update('entity_type', ['parent_entity_type_id' => 100], ['entity_type_id' => 123]);
        $this->update('entity_type', [
            'parent_entity_type_id' => 120,
            'image_url' => 'conveyor_dual'
        ], ['entity_type_id' => 124]);
        $this->update('entity_type', [
            'parent_entity_type_id' => 121,
            'image_url' => 'conveyor_dual'
        ], ['entity_type_id' => 125]);
        $this->update('entity_type', [
            'parent_entity_type_id' => 122,
            'image_url' => 'conveyor_dual'
        ], ['entity_type_id' => 126]);

        $this->update('entity_type', ['parent_entity_type_id' => 100], ['entity_type_id' => 127]);
        $this->update('entity_type', [
            'parent_entity_type_id' => 120,
            'image_url' => 'conveyor_fast_dual'
        ], ['entity_type_id' => 128]);
        $this->update('entity_type', [
            'parent_entity_type_id' => 121,
            'image_url' => 'conveyor_fast_dual'
        ], ['entity_type_id' => 129]);
        $this->update('entity_type', [
            'parent_entity_type_id' => 122,
            'image_url' => 'conveyor_fast_dual'
        ], ['entity_type_id' => 130]);

        echo "    > Reverted conveyor orientation structure\n";
    }
}
