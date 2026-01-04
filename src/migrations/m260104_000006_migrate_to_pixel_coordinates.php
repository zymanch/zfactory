<?php

use yii\db\Migration;

/**
 * Class m260104_000006_migrate_to_pixel_coordinates
 *
 * Migrates from normalized (0-1 DECIMAL) to pixel-based (INT) coordinate system:
 * - position DECIMAL(5,4) → position_px INT
 * - arm_position DECIMAL(5,4) → arm_position_px INT
 * - lateral_offset DECIMAL(5,4) → from_direction ENUM('up','down','left','right')
 */
class m260104_000006_migrate_to_pixel_coordinates extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add new columns
        $this->addColumn('{{%entity_resource}}', 'position_px', $this->integer()->null());
        $this->addColumn('{{%entity_resource}}', 'arm_position_px', $this->integer()->null());
        $this->addColumn('{{%entity_resource}}', 'from_direction',
            "ENUM('up','down','left','right') NULL"
        );

        // Convert existing data (tileWidth=64px hardcoded only for migration)
        $this->execute("
            UPDATE entity_resource
            SET position_px = ROUND(position * 64),
                arm_position_px = ROUND(arm_position * 64)
            WHERE position IS NOT NULL
        ");

        // Set from_direction based on lateral_offset and entity orientation
        // Requires JOIN with entity and entity_type to get orientation
        $this->execute("
            UPDATE entity_resource er
            JOIN entity e ON er.entity_id = e.entity_id
            JOIN entity_type et ON e.entity_type_id = et.entity_type_id
            SET er.from_direction = CASE
                WHEN et.orientation = 'right' AND er.lateral_offset < 0 THEN 'up'
                WHEN et.orientation = 'right' AND er.lateral_offset > 0 THEN 'down'
                WHEN et.orientation = 'down' AND er.lateral_offset < 0 THEN 'right'
                WHEN et.orientation = 'down' AND er.lateral_offset > 0 THEN 'left'
                WHEN et.orientation = 'left' AND er.lateral_offset < 0 THEN 'down'
                WHEN et.orientation = 'left' AND er.lateral_offset > 0 THEN 'up'
                WHEN et.orientation = 'up' AND er.lateral_offset < 0 THEN 'left'
                WHEN et.orientation = 'up' AND er.lateral_offset > 0 THEN 'right'
                ELSE 'down'  -- default
            END
            WHERE er.lateral_offset IS NOT NULL
        ");

        // Drop old columns
        $this->dropColumn('{{%entity_resource}}', 'position');
        $this->dropColumn('{{%entity_resource}}', 'arm_position');
        $this->dropColumn('{{%entity_resource}}', 'lateral_offset');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Restore old columns
        $this->addColumn('{{%entity_resource}}', 'position',
            'DECIMAL(5,4) NULL'
        );
        $this->addColumn('{{%entity_resource}}', 'arm_position',
            'DECIMAL(5,4) NULL'
        );
        $this->addColumn('{{%entity_resource}}', 'lateral_offset',
            'DECIMAL(5,4) NULL'
        );

        // Convert back to normalized coordinates
        $this->execute("
            UPDATE entity_resource
            SET position = position_px / 64.0,
                arm_position = arm_position_px / 64.0
            WHERE position_px IS NOT NULL
        ");

        // from_direction → lateral_offset (simplified reversion)
        $this->execute("
            UPDATE entity_resource er
            JOIN entity e ON er.entity_id = e.entity_id
            JOIN entity_type et ON e.entity_type_id = et.entity_type_id
            SET er.lateral_offset = CASE
                WHEN (et.orientation = 'right' AND er.from_direction = 'up')
                    OR (et.orientation = 'down' AND er.from_direction = 'right')
                    OR (et.orientation = 'left' AND er.from_direction = 'down')
                    OR (et.orientation = 'up' AND er.from_direction = 'left')
                    THEN -0.25
                WHEN (et.orientation = 'right' AND er.from_direction = 'down')
                    OR (et.orientation = 'down' AND er.from_direction = 'left')
                    OR (et.orientation = 'left' AND er.from_direction = 'up')
                    OR (et.orientation = 'up' AND er.from_direction = 'right')
                    THEN 0.25
                ELSE 0
            END
            WHERE er.from_direction IS NOT NULL
        ");

        // Drop new columns
        $this->dropColumn('{{%entity_resource}}', 'position_px');
        $this->dropColumn('{{%entity_resource}}', 'arm_position_px');
        $this->dropColumn('{{%entity_resource}}', 'from_direction');
    }
}
