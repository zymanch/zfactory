<?php

use yii\db\Migration;

/**
 * Rename landing.image_url to landing.folder
 * Store only folder name (e.g., 'grass', 'island_edge') instead of file path
 */
class m251223_140000_rename_landing_image_url_to_folder extends Migration
{
    public function safeUp()
    {
        // Check if column 'folder' already exists (migration may have run under different name)
        $columns = $this->db->getTableSchema('{{%landing}}')->columnNames;
        if (in_array('folder', $columns)) {
            echo "Column 'folder' already exists, skipping...\n";
            return;
        }

        // Step 1: Remove .png extension from existing data
        $this->execute("UPDATE {{%landing}} SET image_url = REPLACE(image_url, '.png', '')");

        // Step 2: Rename column
        $this->renameColumn('{{%landing}}', 'image_url', 'folder');
    }

    public function safeDown()
    {
        $columns = $this->db->getTableSchema('{{%landing}}')->columnNames;
        if (!in_array('folder', $columns)) {
            return;
        }

        $this->renameColumn('{{%landing}}', 'folder', 'image_url');
        $this->execute("UPDATE {{%landing}} SET image_url = CONCAT(image_url, '.png')");
    }
}
