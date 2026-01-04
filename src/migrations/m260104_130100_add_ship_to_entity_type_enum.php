<?php

use yii\db\Migration;

/**
 * Add 'ship' enum value to entity_type.type column
 * Also update entity_type_id=601 to set type='ship'
 */
class m260104_130100_add_ship_to_entity_type_enum extends Migration
{
    public function safeUp()
    {
        // Add 'ship' to entity_type.type enum
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage','hq','ship') NOT NULL");

        // Update entity_type_id=601 to set type='ship'
        $this->update('entity_type', ['type' => 'ship'], ['entity_type_id' => 601]);
    }

    public function safeDown()
    {
        // Set type to empty for entity_type_id=601 before removing enum value
        $this->update('entity_type', ['type' => 'building'], ['entity_type_id' => 601]);

        // Remove 'ship' from entity_type.type enum
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage','hq') NOT NULL");
    }
}
