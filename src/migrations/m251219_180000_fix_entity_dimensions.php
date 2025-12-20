<?php

use yii\db\Migration;

/**
 * Fixes entity_type dimensions to match actual SVG sizes
 */
class m251219_180000_fix_entity_dimensions extends Migration
{
    public function safeUp()
    {
        // Small Furnace: SVG is 32x24, change DB from 2x2 to 1x1
        $this->update('entity_type', ['width' => 1, 'height' => 1], ['entity_type_id' => 101]);

        // Steam Engine: SVG is 96x48, change DB from 2x3 to 3x2
        $this->update('entity_type', ['width' => 3, 'height' => 2], ['entity_type_id' => 106]);

        // Boiler: SVG is 64x24, change DB from 2x2 to 2x1
        $this->update('entity_type', ['width' => 2, 'height' => 1], ['entity_type_id' => 107]);
    }

    public function safeDown()
    {
        $this->update('entity_type', ['width' => 2, 'height' => 2], ['entity_type_id' => 101]);
        $this->update('entity_type', ['width' => 2, 'height' => 3], ['entity_type_id' => 106]);
        $this->update('entity_type', ['width' => 2, 'height' => 2], ['entity_type_id' => 107]);
    }
}
