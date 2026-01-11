<?php

use yii\db\Migration;

/**
 * Add storage columns to entity_type table
 * - storage_type: ENUM('none', 'unlimited', 'limited')
 * - storage_resource_count: max total resources
 * - storage_per_resource: max per resource type
 */
class m260111_140000_add_storage_columns extends Migration
{
    public function safeUp()
    {
        echo "Adding storage columns to entity_type table\n";

        // Add 3 new columns
        $this->addColumn('{{%entity_type}}', 'storage_type', "ENUM('none', 'unlimited', 'limited') NOT NULL DEFAULT 'none' COMMENT 'Storage capacity type'");
        $this->addColumn('{{%entity_type}}', 'storage_resource_count', $this->integer()->unsigned()->null()->comment('Total max resources'));
        $this->addColumn('{{%entity_type}}', 'storage_per_resource', $this->integer()->unsigned()->null()->comment('Max per resource type'));

        echo "Columns added successfully\n";
        echo "Filling values for all entity_type records\n";

        // A) Crafting buildings (101, 103, 107)
        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 10,
            'storage_resource_count' => 50
        ], ['entity_type_id' => [101, 103, 107]]);
        echo "  Updated crafting buildings (101, 103, 107)\n";

        // B) Mining buildings (102, 108, 500-512)
        $miningIds = [102, 108];
        for ($i = 500; $i <= 512; $i++) {
            $miningIds[] = $i;
        }
        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 10,
            'storage_resource_count' => 10
        ], ['entity_type_id' => $miningIds]);
        echo "  Updated mining buildings (102, 108, 500-512)\n";

        // C) Transporters (100, 120-122, 131-132, 140-141) - already 'none' by default
        // No update needed, they stay as 'none'
        echo "  Transporters (100, 120-122, 131-132, 140-141) - storage_type='none' (default)\n";

        // D) Manipulators (200-201, 210-215) - already 'none' by default
        // No update needed
        echo "  Manipulators (200-201, 210-215) - storage_type='none' (default)\n";

        // E) Storage Chest (104)
        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 100,
            'storage_resource_count' => 1000
        ], ['entity_type_id' => 104]);
        echo "  Updated Storage Chest (104)\n";

        // F) Tanks (135, 136)
        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 5000,
            'storage_resource_count' => 5000
        ], ['entity_type_id' => 135]);

        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 25000,
            'storage_resource_count' => 25000
        ], ['entity_type_id' => 136]);
        echo "  Updated Tanks (135, 136)\n";

        // G) Eye entities (400-402) - already 'none' by default
        echo "  Eye entities (400-402) - storage_type='none' (default)\n";

        // H) Batteries (910-912)
        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 100,
            'storage_resource_count' => 100
        ], ['entity_type_id' => 910]);

        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 500,
            'storage_resource_count' => 500
        ], ['entity_type_id' => 911]);

        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 2000,
            'storage_resource_count' => 2000
        ], ['entity_type_id' => 912]);
        echo "  Updated Batteries (910-912)\n";

        // I) Generators (920-922)
        $this->update('{{%entity_type}}', [
            'storage_type' => 'limited',
            'storage_per_resource' => 10,
            'storage_resource_count' => 10
        ], ['entity_type_id' => [920, 921, 922]]);
        echo "  Updated Generators (920-922)\n";

        // J) Pylons (900-902) - already 'none' by default
        echo "  Pylons (900-902) - storage_type='none' (default)\n";

        // K) HQ - special type with unlimited storage
        $this->update('{{%entity_type}}', [
            'storage_type' => 'unlimited',
            'storage_per_resource' => null,
            'storage_resource_count' => null
        ], ['type' => 'special']); // HQ has type='special'
        echo "  Updated HQ (type='special') - unlimited storage\n";

        echo "Migration completed successfully\n";
    }

    public function safeDown()
    {
        echo "Removing storage columns from entity_type table\n";

        $this->dropColumn('{{%entity_type}}', 'storage_per_resource');
        $this->dropColumn('{{%entity_type}}', 'storage_resource_count');
        $this->dropColumn('{{%entity_type}}', 'storage_type');

        echo "Columns removed successfully\n";
    }
}
