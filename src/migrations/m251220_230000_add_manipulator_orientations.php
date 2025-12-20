<?php

use yii\db\Migration;

/**
 * Add orientation variants for Short and Long Manipulators
 * Short Manipulator: 200 (right), 210 (up), 211 (down), 212 (left)
 * Long Manipulator: 201 (right), 213 (up), 214 (down), 215 (left)
 */
class m251220_230000_add_manipulator_orientations extends Migration
{
    public function safeUp()
    {
        // Update existing manipulators to have orientation=right
        $this->update('entity_type', ['orientation' => 'right'], ['entity_type_id' => 200]);
        $this->update('entity_type', ['orientation' => 'right'], ['entity_type_id' => 201]);

        // Short Manipulator orientation variants (parent_entity_type_id = 200)
        $this->insert('entity_type', [
            'entity_type_id' => 210,
            'type' => 'manipulator',
            'name' => 'Short Manipulator',
            'image_url' => 'manipulator_short_up',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_short_up/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 200,
            'orientation' => 'up',
        ]);

        $this->insert('entity_type', [
            'entity_type_id' => 211,
            'type' => 'manipulator',
            'name' => 'Short Manipulator',
            'image_url' => 'manipulator_short_down',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_short_down/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 200,
            'orientation' => 'down',
        ]);

        $this->insert('entity_type', [
            'entity_type_id' => 212,
            'type' => 'manipulator',
            'name' => 'Short Manipulator',
            'image_url' => 'manipulator_short_left',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_short_left/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 200,
            'orientation' => 'left',
        ]);

        // Long Manipulator orientation variants (parent_entity_type_id = 201)
        $this->insert('entity_type', [
            'entity_type_id' => 213,
            'type' => 'manipulator',
            'name' => 'Long Manipulator',
            'image_url' => 'manipulator_long_up',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_long_up/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 201,
            'orientation' => 'up',
        ]);

        $this->insert('entity_type', [
            'entity_type_id' => 214,
            'type' => 'manipulator',
            'name' => 'Long Manipulator',
            'image_url' => 'manipulator_long_down',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_long_down/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 201,
            'orientation' => 'down',
        ]);

        $this->insert('entity_type', [
            'entity_type_id' => 215,
            'type' => 'manipulator',
            'name' => 'Long Manipulator',
            'image_url' => 'manipulator_long_left',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_long_left/icon.svg',
            'power' => 1,
            'parent_entity_type_id' => 201,
            'orientation' => 'left',
        ]);
    }

    public function safeDown()
    {
        // Remove orientation variants
        $this->delete('entity_type', ['entity_type_id' => [210, 211, 212, 213, 214, 215]]);

        // Reset orientation on base manipulators
        $this->update('entity_type', ['orientation' => 'none'], ['entity_type_id' => 200]);
        $this->update('entity_type', ['orientation' => 'none'], ['entity_type_id' => 201]);
    }
}
