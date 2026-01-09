<?php

use yii\db\Migration;

/**
 * Class m260109_135158_refactor_sprite_folders
 */
class m260109_135158_refactor_sprite_folders extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Rename image_url to folder in entity_type table
        $this->renameColumn('entity_type', 'image_url', 'folder');

        // Rename image_url to folder in deposit_type table
        $this->renameColumn('deposit_type', 'image_url', 'folder');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert entity_type column rename
        $this->renameColumn('entity_type', 'folder', 'image_url');

        // Revert deposit_type column rename
        $this->renameColumn('deposit_type', 'folder', 'image_url');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260109_135158_refactor_sprite_folders cannot be reverted.\n";

        return false;
    }
    */
}
