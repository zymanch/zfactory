<?php

use yii\db\Migration;

/**
 * Fix invalid position_px values in entity_resource for manipulators
 * Clamps position_px to valid range [0, 30]
 */
class m260126_000000_fix_manipulator_position_px extends Migration
{
    public function safeUp()
    {
        // Fix negative or too large position_px values
        // Clamp to [0, 30] range
        $this->execute("
            UPDATE entity_resource er
            JOIN entity e ON er.entity_id = e.entity_id
            JOIN entity_type et ON e.entity_type_id = et.entity_type_id
            SET er.position_px = GREATEST(0, LEAST(30, COALESCE(er.position_px, 0)))
            WHERE et.type = 'manipulator'
            AND (er.position_px < 0 OR er.position_px > 30 OR er.position_px IS NULL)
        ");

        echo "Fixed invalid position_px values for manipulators.\n";
    }

    public function safeDown()
    {
        echo "Cannot revert position_px fixes.\n";
        return true;
    }
}
