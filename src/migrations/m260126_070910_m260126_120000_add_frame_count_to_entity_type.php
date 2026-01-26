<?php

use yii\db\Migration;

/**
 * Class m260126_070910_m260126_120000_add_frame_count_to_entity_type
 */
class m260126_070910_m260126_120000_add_frame_count_to_entity_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add frame_count column if not exists
        $tableSchema = $this->db->schema->getTableSchema('entity_type');
        if (!isset($tableSchema->columns['frame_count'])) {
            $this->addColumn('entity_type', 'frame_count', $this->integer()->null());
        }

        // Calculate and set frame_count for all manipulators
        // Formula: (max(total_width, total_height) × 4) + 1
        // total_width = width + width_overflow
        // total_height = height + height_overflow
        $this->execute("
            UPDATE entity_type
            SET frame_count = (GREATEST(width + width_overflow, height + height_overflow) * 4) + 1
            WHERE type = 'manipulator'
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('entity_type', 'frame_count');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260126_070910_m260126_120000_add_frame_count_to_entity_type cannot be reverted.\n";

        return false;
    }
    */
}
