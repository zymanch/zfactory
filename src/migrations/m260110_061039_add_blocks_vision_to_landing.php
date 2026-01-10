<?php

use yii\db\Migration;

/**
 * Class m260110_061039_add_blocks_vision_to_landing
 */
class m260110_061039_add_blocks_vision_to_landing extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add blocks_vision column to landing table
        $this->addColumn('{{%landing}}', 'blocks_vision', $this->string(3)->notNull()->defaultValue('no'));

        // Set blocks_vision='yes' for Stone and Island Edge
        // Stone (landing_id=5) - rocky terrain blocks line of sight
        // Island Edge (landing_id=10) - hanging stalactites block line of sight
        $this->update('{{%landing}}', ['blocks_vision' => 'yes'], ['landing_id' => [5, 10]]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%landing}}', 'blocks_vision');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260110_061039_add_blocks_vision_to_landing cannot be reverted.\n";

        return false;
    }
    */
}
