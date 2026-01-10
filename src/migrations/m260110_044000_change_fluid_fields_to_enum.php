<?php

use yii\db\Migration;

/**
 * Changes is_water/is_lava to single fluid_type ENUM column
 * More correct design - mutually exclusive values
 */
class m260110_044000_change_fluid_fields_to_enum extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add new fluid_type column
        $this->addColumn('landing', 'fluid_type', "ENUM('none','water','lava') NOT NULL DEFAULT 'none' AFTER is_buildable");

        // Migrate data from is_water/is_lava to fluid_type
        $this->update('landing', ['fluid_type' => 'water'], ['is_water' => 'yes']);
        $this->update('landing', ['fluid_type' => 'lava'], ['is_lava' => 'yes']);

        // Drop old columns
        $this->dropColumn('landing', 'is_water');
        $this->dropColumn('landing', 'is_lava');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Re-add old columns
        $this->addColumn('landing', 'is_water', "ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER is_buildable");
        $this->addColumn('landing', 'is_lava', "ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER is_water");

        // Migrate data back
        $this->update('landing', ['is_water' => 'yes'], ['fluid_type' => 'water']);
        $this->update('landing', ['is_lava' => 'yes'], ['fluid_type' => 'lava']);

        // Drop new column
        $this->dropColumn('landing', 'fluid_type');
    }
}
