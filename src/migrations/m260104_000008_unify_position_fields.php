<?php

use yii\db\Migration;

/**
 * Class m260104_000008_unify_position_fields
 *
 * Simplifies entity_resource table by:
 * 1. Removing arm_position_px (merge into position_px)
 * 2. Making position_px centered: 0 = center, negative = towards center, positive = from center
 */
class m260104_000008_unify_position_fields extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Convert existing position_px to centered coordinates (0 = center)
        // For conveyors: position_px 0-64 → -32 to +32 (assuming tileWidth=64)
        $this->execute("
            UPDATE entity_resource
            SET position_px = position_px - 32
            WHERE position_px IS NOT NULL
        ");

        // Convert arm_position_px to centered position_px for manipulators
        // For short manipulators (center=96): 0-192 → -96 to +96
        // For long manipulators (center=160): 0-320 → -160 to +160
        // We need to join with entity_type to get center_position_px
        $this->execute("
            UPDATE entity_resource er
            JOIN entity e ON er.entity_id = e.entity_id
            JOIN entity_type et ON e.entity_type_id = et.entity_type_id
            SET er.position_px = er.arm_position_px - et.center_position_px
            WHERE er.arm_position_px IS NOT NULL
              AND et.center_position_px IS NOT NULL
        ");

        // Drop arm_position_px column
        $this->dropColumn('{{%entity_resource}}', 'arm_position_px');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Re-add arm_position_px column
        $this->addColumn('{{%entity_resource}}', 'arm_position_px', $this->integer()->null());

        // Convert centered position_px back to 0-based for manipulators
        $this->execute("
            UPDATE entity_resource er
            JOIN entity e ON er.entity_id = e.entity_id
            JOIN entity_type et ON e.entity_type_id = et.entity_type_id
            SET er.arm_position_px = er.position_px + et.center_position_px
            WHERE et.type = 'manipulator'
              AND er.position_px IS NOT NULL
              AND et.center_position_px IS NOT NULL
        ");

        // Convert centered position_px back to 0-based for conveyors
        $this->execute("
            UPDATE entity_resource er
            JOIN entity e ON er.entity_id = e.entity_id
            JOIN entity_type et ON e.entity_type_id = et.entity_type_id
            SET er.position_px = er.position_px + 32
            WHERE et.type = 'transporter'
              AND er.position_px IS NOT NULL
        ");
    }
}
