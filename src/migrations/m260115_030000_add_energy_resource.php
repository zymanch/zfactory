<?php

use yii\db\Migration;

/**
 * Extends resource table:
 * 1. Add 'energy' value to type ENUM
 * 2. Insert Electricity resource (resource_id=400) if not exists
 */
class m260115_030000_add_energy_resource extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // 1. Add 'energy' to type ENUM
        $this->alterColumn('{{%resource}}', 'type',
            "ENUM('raw','liquid','crafted','deposit','energy') NOT NULL DEFAULT 'raw'"
        );
        echo "✓ Added 'energy' to resource.type ENUM\n";

        // 2. Check if Electricity resource exists
        $exists = $this->db->createCommand(
            "SELECT COUNT(*) FROM {{%resource}} WHERE resource_id = 400"
        )->queryScalar();

        if (!$exists) {
            $this->insert('{{%resource}}', [
                'resource_id' => 400,
                'name' => 'Electricity',
                'icon_url' => 'electricity.svg',
                'type' => 'energy'
            ]);
            echo "✓ Inserted Electricity resource (resource_id=400)\n";
        } else {
            // Update existing resource to 'energy' type
            $this->update('{{%resource}}', ['type' => 'energy'], ['resource_id' => 400]);
            echo "✓ Updated existing Electricity resource to type='energy'\n";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove Electricity resource
        $this->delete('{{%resource}}', ['resource_id' => 400]);

        // Revert type ENUM
        $this->alterColumn('{{%resource}}', 'type',
            "ENUM('raw','liquid','crafted','deposit') NOT NULL DEFAULT 'raw'"
        );

        echo "✓ Removed Electricity resource\n";
        echo "✓ Reverted resource.type ENUM\n";
    }
}
