<?php

use yii\db\Migration;

/**
 * Populates resource_types column for all entity types
 * - Conveyors: 'raw,crafted'
 * - Pipes: 'liquid'
 * - Mining: 'deposit,raw'
 * - Buildings: 'raw,crafted,liquid'
 * - Storage: 'raw,crafted,liquid'
 * - Electricity: 'energy'
 */
class m260115_020000_populate_resource_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Conveyors (type='transporter', excluding pipes 131,132,135,136,140,141)
        $this->execute("
            UPDATE {{%entity_type}}
            SET resource_types = 'raw,crafted'
            WHERE type = 'transporter'
              AND entity_type_id NOT IN (131, 132, 135, 136, 140, 141)
        ");
        echo "✓ Conveyors: resource_types = 'raw,crafted'\n";

        // Pipes (131, 132, 135, 136, 140, 141)
        $this->execute("
            UPDATE {{%entity_type}}
            SET resource_types = 'liquid'
            WHERE entity_type_id IN (131, 132, 135, 136, 140, 141)
        ");
        echo "✓ Pipes: resource_types = 'liquid'\n";

        // Mining buildings
        $this->execute("
            UPDATE {{%entity_type}}
            SET resource_types = 'deposit,raw'
            WHERE type = 'mining'
        ");
        echo "✓ Mining: resource_types = 'deposit,raw'\n";

        // Production buildings
        $this->execute("
            UPDATE {{%entity_type}}
            SET resource_types = 'raw,crafted,liquid'
            WHERE type = 'building'
        ");
        echo "✓ Buildings: resource_types = 'raw,crafted,liquid'\n";

        // Storage buildings
        $this->execute("
            UPDATE {{%entity_type}}
            SET resource_types = 'raw,crafted,liquid'
            WHERE type = 'storage'
        ");
        echo "✓ Storage: resource_types = 'raw,crafted,liquid'\n";

        // Electricity buildings (generators, batteries, pylons)
        $this->execute("
            UPDATE {{%entity_type}}
            SET resource_types = 'energy'
            WHERE type = 'electricity'
        ");
        echo "✓ Electricity: resource_types = 'energy'\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Clear all resource_types
        $this->execute("UPDATE {{%entity_type}} SET resource_types = NULL");
        echo "✓ Cleared all resource_types\n";
    }
}
