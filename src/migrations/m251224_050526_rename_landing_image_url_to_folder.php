<?php

use yii\db\Migration;

/**
 * Rename landing.image_url to landing.folder
 * Store only folder name (e.g., 'grass', 'island_edge') instead of file path
 */
class m251224_050526_rename_landing_image_url_to_folder extends Migration
{
    public function safeUp()
    {
        // Step 1: Remove .png extension from existing data
        $this->execute("UPDATE {{%landing}} SET image_url = REPLACE(image_url, '.png', '')");

        // Step 2: Rename column
        $this->renameColumn('{{%landing}}', 'image_url', 'folder');

        echo "✓ Column renamed: landing.image_url → landing.folder\n";
        echo "✓ Data updated: removed .png extensions\n";
    }

    public function safeDown()
    {
        // Step 1: Rename column back
        $this->renameColumn('{{%landing}}', 'folder', 'image_url');

        // Step 2: Add .png extension back
        $this->execute("UPDATE {{%landing}} SET image_url = CONCAT(image_url, '.png')");

        echo "✓ Reverted: landing.folder → landing.image_url\n";
        echo "✓ Data reverted: added .png extensions back\n";
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251224_050526_rename_landing_image_url_to_folder cannot be reverted.\n";

        return false;
    }
    */
}
