<?php

use yii\db\Migration;

/**
 * Class m260104_000005_add_center_position_px
 *
 * Adds center_position_px column to entity_type table.
 * This column stores the center point in pixels for dual-lane conveyors and manipulators.
 */
class m260104_000005_add_center_position_px extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Check if column already exists
        $tableSchema = Yii::$app->db->schema->getTableSchema('{{%entity_type}}');
        if ($tableSchema->getColumn('center_position_px') === null) {
            $this->addColumn('{{%entity_type}}', 'center_position_px',
                $this->integer()->null()->after('power')
            );
        }

        // Set values for conveyors (tileWidth=64px)
        $this->update('{{%entity_type}}',
            ['center_position_px' => 32],
            ['type' => 'transporter']
        );

        // Set values for Short Manipulators (reach=1)
        $this->execute("
            UPDATE entity_type
            SET center_position_px = 96
            WHERE type = 'manipulator' AND name LIKE '%Short%'
        ");

        // Set values for Long Manipulators (reach=2)
        $this->execute("
            UPDATE entity_type
            SET center_position_px = 160
            WHERE type = 'manipulator' AND name LIKE '%Long%'
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%entity_type}}', 'center_position_px');
    }
}
