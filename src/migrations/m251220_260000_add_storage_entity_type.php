<?php

use yii\db\Migration;

/**
 * Add 'storage' type for storage entities (chests, containers)
 */
class m251220_260000_add_storage_entity_type extends Migration
{
    public function safeUp()
    {
        // Add 'storage' to entity_type.type enum
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage') NOT NULL");

        // Update Storage Chest to new type
        $this->update('entity_type', ['type' => 'storage'], ['entity_type_id' => 104]);
    }

    public function safeDown()
    {
        // Revert to building type
        $this->update('entity_type', ['type' => 'building'], ['entity_type_id' => 104]);

        // Remove 'storage' from enum
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining') NOT NULL");
    }
}
