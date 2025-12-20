<?php

use yii\db\Migration;

/**
 * Migration: Convert entity x,y from pixel coordinates to tile coordinates
 *
 * Before: x=512, y=48 (pixels)
 * After: x=16, y=2 (tiles, matching map table format)
 *
 * Formula: tile_x = floor(pixel_x / 32), tile_y = floor(pixel_y / 24)
 */
class m251220_210000_convert_entity_coords_to_tiles extends Migration
{
    public function safeUp()
    {
        // Convert pixel coordinates to tile coordinates
        // tile_width = 32, tile_height = 24
        $this->execute('UPDATE entity SET x = FLOOR(x / 32), y = FLOOR(y / 24)');
    }

    public function safeDown()
    {
        // Convert tile coordinates back to pixel coordinates
        $this->execute('UPDATE entity SET x = x * 32, y = y * 24');
    }
}
