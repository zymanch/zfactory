<?php

use yii\db\Migration;

/**
 * Adds is_water and is_lava fields to landing table
 * Needed for fluid pump placement validation
 */
class m260110_043730_add_fluid_fields_to_landing extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add is_water column (default 'no')
        $this->addColumn('landing', 'is_water', "ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER is_buildable");

        // Add is_lava column (default 'no')
        $this->addColumn('landing', 'is_lava', "ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER is_water");

        // Set is_water='yes' for Water landing (landing_id=4)
        $this->update('landing', ['is_water' => 'yes'], ['landing_id' => 4]);

        // Set is_lava='yes' for Lava landing (landing_id=6)
        $this->update('landing', ['is_lava' => 'yes'], ['landing_id' => 6]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('landing', 'is_lava');
        $this->dropColumn('landing', 'is_water');
    }
}
