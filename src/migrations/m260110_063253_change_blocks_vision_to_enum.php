<?php

use yii\db\Migration;

/**
 * Class m260110_063253_change_blocks_vision_to_enum
 */
class m260110_063253_change_blocks_vision_to_enum extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Change blocks_vision from VARCHAR(3) to ENUM('yes','no')
        $this->alterColumn('{{%landing}}', 'blocks_vision', "ENUM('yes','no') NOT NULL DEFAULT 'no'");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Revert back to VARCHAR(3)
        $this->alterColumn('{{%landing}}', 'blocks_vision', $this->string(3)->notNull()->defaultValue('no'));
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260110_063253_change_blocks_vision_to_enum cannot be reverted.\n";

        return false;
    }
    */
}
