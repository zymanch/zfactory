<?php

use yii\db\Migration;

/**
 * Migration: Add 'mining' type to entity_type enum, rename landing.is_walk to is_buildable,
 * update Mining Drill and add Fast Mining Drill
 */
class m251220_200000_add_mining_type_and_buildable extends Migration
{
    public function safeUp()
    {
        // 1. Extend type enum to include 'mining'
        $this->alterColumn('entity_type', 'type',
            "ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining') NOT NULL");

        // 2. Update Mining Drill (id=102) to type 'mining'
        $this->update('entity_type', ['type' => 'mining'], ['entity_type_id' => 102]);

        // 3. Add Fast Mining Drill (id=108)
        $this->insert('entity_type', [
            'entity_type_id' => 108,
            'type' => 'mining',
            'name' => 'Fast Mining Drill',
            'image_url' => 'drill_fast',
            'extension' => 'svg',
            'max_durability' => 250,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'drill_fast/icon.svg',
            'power' => 1
        ]);

        // 4. Rename landing.is_walk to is_buildable
        $this->renameColumn('landing', 'is_walk', 'is_buildable');

        // 5. Update landing values: stone (id=5) and swamp (id=8) cannot be built on
        // Water (4), Lava (6), Sky (9), Island Edge (10) already have 'no'
        $this->update('landing', ['is_buildable' => 'no'], ['landing_id' => 5]); // Stone
        $this->update('landing', ['is_buildable' => 'no'], ['landing_id' => 8]); // Swamp
    }

    public function safeDown()
    {
        // Revert landing values
        $this->update('landing', ['is_buildable' => 'yes'], ['landing_id' => 5]);
        $this->update('landing', ['is_buildable' => 'yes'], ['landing_id' => 8]);

        // Rename is_buildable back to is_walk
        $this->renameColumn('landing', 'is_buildable', 'is_walk');

        // Delete Fast Mining Drill
        $this->delete('entity_type', ['entity_type_id' => 108]);

        // Revert Mining Drill to 'building' type
        $this->update('entity_type', ['type' => 'building'], ['entity_type_id' => 102]);

        // Revert type enum
        $this->alterColumn('entity_type', 'type',
            "ENUM('building','transporter','manipulator','tree','relief','resource','eye') NOT NULL");
    }
}
