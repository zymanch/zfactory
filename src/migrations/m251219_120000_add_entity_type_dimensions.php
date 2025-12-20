<?php

use yii\db\Migration;

/**
 * Migration: Add width/height columns and icon_url to entity_type
 */
class m251219_120000_add_entity_type_dimensions extends Migration
{
    public function safeUp()
    {
        // Add width column (in tiles, default 1)
        $this->addColumn('entity_type', 'width', $this->tinyInteger()->unsigned()->notNull()->defaultValue(1)->after('max_durability'));

        // Add height column (in tiles, default 1)
        $this->addColumn('entity_type', 'height', $this->tinyInteger()->unsigned()->notNull()->defaultValue(1)->after('width'));

        // Add icon_url column (32x24 icon for UI panels)
        $this->addColumn('entity_type', 'icon_url', $this->string(256)->null()->after('height'));

        // Update some entities to have larger sizes
        // Furnace: 2x2
        $this->update('entity_type', ['width' => 2, 'height' => 2], ['entity_type_id' => 101]);

        // Assembler: 3x3
        $this->update('entity_type', ['width' => 3, 'height' => 3], ['entity_type_id' => 103]);

        // Steam Engine: 2x3
        $this->update('entity_type', ['width' => 2, 'height' => 3], ['entity_type_id' => 106]);

        // Boiler: 2x2
        $this->update('entity_type', ['width' => 2, 'height' => 2], ['entity_type_id' => 107]);

        // Set icon URLs for all building entities
        $this->update('entity_type', ['icon_url' => 'conveyor/icon.svg'], ['entity_type_id' => 100]);
        $this->update('entity_type', ['icon_url' => 'furnace/icon.svg'], ['entity_type_id' => 101]);
        $this->update('entity_type', ['icon_url' => 'drill/icon.svg'], ['entity_type_id' => 102]);
        $this->update('entity_type', ['icon_url' => 'assembler/icon.svg'], ['entity_type_id' => 103]);
        $this->update('entity_type', ['icon_url' => 'chest/icon.svg'], ['entity_type_id' => 104]);
        $this->update('entity_type', ['icon_url' => 'power_pole/icon.svg'], ['entity_type_id' => 105]);
        $this->update('entity_type', ['icon_url' => 'steam_engine/icon.svg'], ['entity_type_id' => 106]);
        $this->update('entity_type', ['icon_url' => 'boiler/icon.svg'], ['entity_type_id' => 107]);
    }

    public function safeDown()
    {
        $this->dropColumn('entity_type', 'icon_url');
        $this->dropColumn('entity_type', 'height');
        $this->dropColumn('entity_type', 'width');
    }
}
