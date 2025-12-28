<?php

use yii\db\Migration;

/**
 * Add transport fields to entity_resource table
 * Merges entity_transport functionality into entity_resource
 */
class m251227_140644_add_transport_fields_to_entity_resource extends Migration
{
    public function safeUp()
    {
        // Add transport-related columns to entity_resource
        // These are NULL by default because not all entities need transport state
        // (e.g., furnaces, assemblers, storage don't have position/arm_position)

        $this->addColumn('entity_resource', 'position', $this->decimal(5, 4)->null()->comment('Resource position on conveyor (0-1)'));
        $this->addColumn('entity_resource', 'lateral_offset', $this->decimal(5, 4)->null()->comment('Lateral offset on conveyor'));
        $this->addColumn('entity_resource', 'arm_position', $this->decimal(5, 4)->null()->comment('Arm position for manipulators (0-1)'));
        $this->addColumn('entity_resource', 'status', "ENUM('empty','carrying','waiting_transfer','idle','picking','placing') NULL COMMENT 'Transport status'");

        // Migrate data from entity_transport to entity_resource
        // entity_transport has entity_id as PK, so it's 1:1 with current resource on that entity
        $this->execute("
            INSERT INTO entity_resource (entity_id, resource_id, amount, position, lateral_offset, arm_position, status)
            SELECT
                et.entity_id,
                et.resource_id,
                et.amount,
                et.position,
                et.lateral_offset,
                et.arm_position,
                et.status
            FROM entity_transport et
            WHERE et.resource_id IS NOT NULL
            ON DUPLICATE KEY UPDATE
                amount = VALUES(amount),
                position = VALUES(position),
                lateral_offset = VALUES(lateral_offset),
                arm_position = VALUES(arm_position),
                status = VALUES(status)
        ");
    }

    public function safeDown()
    {
        $this->dropColumn('entity_resource', 'status');
        $this->dropColumn('entity_resource', 'arm_position');
        $this->dropColumn('entity_resource', 'lateral_offset');
        $this->dropColumn('entity_resource', 'position');
    }
}
