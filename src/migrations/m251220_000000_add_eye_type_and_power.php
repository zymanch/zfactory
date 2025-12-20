<?php

use yii\db\Migration;

/**
 * Migration: Add 'eye' type to enum and 'power' column to entity_type
 * Also adds new entity types: manipulators, resources, crystal towers
 */
class m251220_000000_add_eye_type_and_power extends Migration
{
    public function safeUp()
    {
        // 1. Extend type enum to include 'eye'
        $this->alterColumn('entity_type', 'type',
            "ENUM('building','transporter','manipulator','tree','relief','resource','eye') NOT NULL");

        // 2. Add power column (visibility radius for eye entities, default 1)
        $this->addColumn('entity_type', 'power',
            $this->integer()->unsigned()->notNull()->defaultValue(1)->after('icon_url'));

        // 3. Insert new entity types

        // Manipulators (200-201)
        $this->insert('entity_type', [
            'entity_type_id' => 200,
            'type' => 'manipulator',
            'name' => 'Short Manipulator',
            'image_url' => 'manipulator_short',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_short/icon.svg',
            'power' => 1
        ]);
        $this->insert('entity_type', [
            'entity_type_id' => 201,
            'type' => 'manipulator',
            'name' => 'Long Manipulator',
            'image_url' => 'manipulator_long',
            'extension' => 'svg',
            'max_durability' => 80,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'manipulator_long/icon.svg',
            'power' => 1
        ]);

        // Resources (300-301)
        $this->insert('entity_type', [
            'entity_type_id' => 300,
            'type' => 'resource',
            'name' => 'Iron Ore',
            'image_url' => 'ore_iron',
            'extension' => 'svg',
            'max_durability' => 9999,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'ore_iron/icon.svg',
            'power' => 1
        ]);
        $this->insert('entity_type', [
            'entity_type_id' => 301,
            'type' => 'resource',
            'name' => 'Copper Ore',
            'image_url' => 'ore_copper',
            'extension' => 'svg',
            'max_durability' => 9999,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'ore_copper/icon.svg',
            'power' => 1
        ]);

        // Crystal Towers - eye type (400-402)
        $this->insert('entity_type', [
            'entity_type_id' => 400,
            'type' => 'eye',
            'name' => 'Small Crystal Tower',
            'image_url' => 'tower_crystal_small',
            'extension' => 'svg',
            'max_durability' => 100,
            'width' => 1,
            'height' => 1,
            'icon_url' => 'tower_crystal_small/icon.svg',
            'power' => 7
        ]);
        $this->insert('entity_type', [
            'entity_type_id' => 401,
            'type' => 'eye',
            'name' => 'Medium Crystal Tower',
            'image_url' => 'tower_crystal_medium',
            'extension' => 'svg',
            'max_durability' => 200,
            'width' => 1,
            'height' => 2,
            'icon_url' => 'tower_crystal_medium/icon.svg',
            'power' => 15
        ]);
        $this->insert('entity_type', [
            'entity_type_id' => 402,
            'type' => 'eye',
            'name' => 'Large Crystal Tower',
            'image_url' => 'tower_crystal_large',
            'extension' => 'svg',
            'max_durability' => 300,
            'width' => 2,
            'height' => 3,
            'icon_url' => 'tower_crystal_large/icon.svg',
            'power' => 30
        ]);
    }

    public function safeDown()
    {
        // Remove new entity types
        $this->delete('entity_type', ['entity_type_id' => [200, 201, 300, 301, 400, 401, 402]]);

        // Remove power column
        $this->dropColumn('entity_type', 'power');

        // Revert type enum
        $this->alterColumn('entity_type', 'type',
            "ENUM('building','transporter','manipulator','tree','relief','resource') NOT NULL");
    }
}
