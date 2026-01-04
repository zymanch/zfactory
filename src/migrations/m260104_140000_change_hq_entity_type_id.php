<?php

use yii\db\Migration;

/**
 * Change HQ entity_type_id from 0 to 900
 * This avoids JavaScript falsy value issues with entity_type_id=0
 */
class m260104_140000_change_hq_entity_type_id extends Migration
{
    public function safeUp()
    {
        // First, update any existing HQ entities to use new ID
        $this->update('entity', ['entity_type_id' => 900], ['entity_type_id' => 0]);
        $this->update('ship_entity', ['entity_type_id' => 900], ['entity_type_id' => 0]);

        // Update entity_type_cost table if exists
        $this->update('entity_type_cost', ['entity_type_id' => 900], ['entity_type_id' => 0]);

        // Now update the entity_type record itself
        $this->update('entity_type', ['entity_type_id' => 900], ['entity_type_id' => 0]);

        echo "    > Changed HQ entity_type_id from 0 to 900\n";
    }

    public function safeDown()
    {
        // Reverse the changes
        $this->update('entity', ['entity_type_id' => 0], ['entity_type_id' => 900]);
        $this->update('ship_entity', ['entity_type_id' => 0], ['entity_type_id' => 900]);
        $this->update('entity_type_cost', ['entity_type_id' => 0], ['entity_type_id' => 900]);
        $this->update('entity_type', ['entity_type_id' => 0], ['entity_type_id' => 900]);

        echo "    > Reverted HQ entity_type_id from 900 to 0\n";
    }
}
