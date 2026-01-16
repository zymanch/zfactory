<?php
use yii\db\Migration;

/**
 * Migration: Add input_connections and output_connections columns to entity_type table
 *
 * These columns store connection points for conveyor-like entities:
 * - input_connections: Directions from which entity can receive resources
 * - output_connections: Directions to which entity can output resources
 *
 * Values support both simple directions (up, down, left, right) and positioned
 * directions for large entities (up_1, up_2, etc).
 */
class m260116_150000_add_conveyor_io_connections extends Migration
{
    public function safeUp()
    {
        $this->addColumn('entity_type', 'input_connections',
            "SET('up','up_1','up_2','down','down_1','down_2','left','left_1','left_2','right','right_1','right_2') DEFAULT NULL COMMENT 'Directions from which entity can receive resources'"
        );

        $this->addColumn('entity_type', 'output_connections',
            "SET('up','up_1','up_2','down','down_1','down_2','left','left_1','left_2','right','right_1','right_2') DEFAULT NULL COMMENT 'Directions to which entity can output resources'"
        );
    }

    public function safeDown()
    {
        $this->dropColumn('entity_type', 'output_connections');
        $this->dropColumn('entity_type', 'input_connections');
    }
}
