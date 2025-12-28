<?php

use yii\db\Migration;

/**
 * Set construction_ticks to 600 (10 seconds) for all entity types
 */
class m251228_120000_set_construction_time_10_seconds extends Migration
{
    public function safeUp()
    {
        // 10 seconds = 600 ticks (60 ticks per second)
        $this->update('entity_type', ['construction_ticks' => 600]);

        echo "Updated construction_ticks to 600 (10 seconds) for all entity types.\n";
    }

    public function safeDown()
    {
        echo "No rollback - construction_ticks values not restored.\n";
    }
}
