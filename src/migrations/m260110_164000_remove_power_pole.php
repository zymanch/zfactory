<?php

use yii\db\Migration;

/**
 * Remove obsolete power_pole entity type (replaced by new electricity system with pylons)
 */
class m260110_164000_remove_power_pole extends Migration
{
    public function safeUp()
    {
        // 1. Delete all power_pole entities from map
        $this->delete('{{%entity}}', ['entity_type_id' => 105]);
        echo "✓ Deleted power_pole entities from map\n";

        // 2. Delete entity_type_cost
        $this->delete('{{%entity_type_cost}}', ['entity_type_id' => 105]);
        echo "✓ Deleted power_pole costs\n";

        // 3. Delete entity_type
        $this->delete('{{%entity_type}}', ['entity_type_id' => 105]);
        echo "✓ Deleted power_pole entity_type\n";
    }

    public function safeDown()
    {
        // 1. Restore entity_type
        $this->insert('{{%entity_type}}', [
            'entity_type_id' => 105,
            'type' => 'building',
            'name' => 'Power Pole',
            'folder' => 'power_pole',
            'extension' => 'png',
            'max_durability' => 100,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'power_pole/normal.png',
            'power' => NULL,
            'parent_entity_type_id' => NULL,
            'orientation' => 'none',
            'animation_fps' => NULL,
            'description' => 'Obsolete power pole (use new electricity system)',
            'construction_ticks' => 60,
        ]);

        echo "✓ Restored power_pole entity_type\n";
        echo "⚠ Note: Entities and costs were NOT restored (manual restoration needed)\n";
    }
}
