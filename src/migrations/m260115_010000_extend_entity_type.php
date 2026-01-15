<?php

use yii\db\Migration;

/**
 * Extends entity_type table:
 * 1. Add 'horizontal' and 'vertical' values to orientation ENUM
 * 2. Add resource_types SET column
 * 3. Update pipe entities (131, 132) with new orientation values
 */
class m260115_010000_extend_entity_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Add 'horizontal' and 'vertical' to orientation ENUM
        $this->alterColumn('{{%entity_type}}', 'orientation',
            "ENUM('none','up','right','down','left','horizontal','vertical') NOT NULL DEFAULT 'none'"
        );

        // 2. Update existing pipes with new orientation values
        $this->update('{{%entity_type}}', ['orientation' => 'horizontal'], ['entity_type_id' => 131]);
        $this->update('{{%entity_type}}', ['orientation' => 'vertical'], ['entity_type_id' => 132]);

        // 3. Add resource_types SET column
        $this->addColumn('{{%entity_type}}', 'resource_types',
            "SET('raw','liquid','crafted','deposit','energy') NULL COMMENT 'Accepted resource types'"
        );

        echo "✓ Added 'horizontal','vertical' to orientation ENUM\n";
        echo "✓ Updated pipes (131, 132) orientation\n";
        echo "✓ Added resource_types SET column\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove resource_types column
        $this->dropColumn('{{%entity_type}}', 'resource_types');

        // Revert pipes to 'none' orientation
        $this->update('{{%entity_type}}', ['orientation' => 'none'], ['entity_type_id' => [131, 132]]);

        // Revert orientation ENUM to original values
        $this->alterColumn('{{%entity_type}}', 'orientation',
            "ENUM('none','up','right','down','left') NOT NULL DEFAULT 'none'"
        );

        echo "✓ Reverted orientation ENUM\n";
        echo "✓ Removed resource_types column\n";
    }
}
