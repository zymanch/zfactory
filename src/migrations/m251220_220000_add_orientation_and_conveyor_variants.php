<?php

use yii\db\Migration;

/**
 * Migration: Add orientation system for rotatable entities
 *
 * - Add parent_entity_type_id column (for grouping rotation variants)
 * - Add orientation column (none, up, right, down, left)
 * - Update Conveyor Belt to type='transporter' with orientation='right'
 * - Add conveyor variants for other orientations
 */
class m251220_220000_add_orientation_and_conveyor_variants extends Migration
{
    public function safeUp()
    {
        // 1. Add parent_entity_type_id column
        $this->addColumn('entity_type', 'parent_entity_type_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER power');

        // 2. Add orientation column
        $this->addColumn('entity_type', 'orientation', "ENUM('none','up','right','down','left') NOT NULL DEFAULT 'none' AFTER parent_entity_type_id");

        // 3. Update Conveyor Belt (id=100) to transporter type with right orientation
        $this->update('entity_type', [
            'type' => 'transporter',
            'orientation' => 'right'
        ], ['entity_type_id' => 100]);

        // 4. Add conveyor variants for other orientations
        // Conveyor Belt Up
        $this->insert('entity_type', [
            'entity_type_id' => 120,
            'type' => 'transporter',
            'name' => 'Conveyor Belt',
            'image_url' => 'conveyor_up',
            'extension' => 'svg',
            'max_durability' => 100,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'conveyor_up/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 100,
            'orientation' => 'up'
        ]);

        // Conveyor Belt Down
        $this->insert('entity_type', [
            'entity_type_id' => 121,
            'type' => 'transporter',
            'name' => 'Conveyor Belt',
            'image_url' => 'conveyor_down',
            'extension' => 'svg',
            'max_durability' => 100,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'conveyor_down/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 100,
            'orientation' => 'down'
        ]);

        // Conveyor Belt Left
        $this->insert('entity_type', [
            'entity_type_id' => 122,
            'type' => 'transporter',
            'name' => 'Conveyor Belt',
            'image_url' => 'conveyor_left',
            'extension' => 'svg',
            'max_durability' => 100,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'conveyor_left/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 100,
            'orientation' => 'left'
        ]);
    }

    public function safeDown()
    {
        // Delete conveyor variants
        $this->delete('entity_type', ['entity_type_id' => [120, 121, 122]]);

        // Revert Conveyor Belt type
        $this->update('entity_type', [
            'type' => 'building',
            'orientation' => 'none'
        ], ['entity_type_id' => 100]);

        // Drop columns
        $this->dropColumn('entity_type', 'orientation');
        $this->dropColumn('entity_type', 'parent_entity_type_id');
    }
}
