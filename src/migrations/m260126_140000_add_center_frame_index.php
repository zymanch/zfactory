<?php

use yii\db\Migration;

/**
 * Add center_frame_index to entity_type table
 */
class m260126_140000_add_center_frame_index extends Migration
{
    public function safeUp()
    {
        // Add center_frame_index column
        $tableSchema = $this->db->schema->getTableSchema('entity_type');
        if (!isset($tableSchema->columns['center_frame_index'])) {
            $this->addColumn('entity_type', 'center_frame_index', $this->integer()->null());
        }

        // Calculate center_frame_index for manipulators
        $this->execute("
            UPDATE entity_type
            SET center_frame_index = FLOOR(frame_count / 2)
            WHERE type = 'manipulator' AND frame_count IS NOT NULL
        ");
    }

    public function safeDown()
    {
        $this->dropColumn('entity_type', 'center_frame_index');
    }
}
