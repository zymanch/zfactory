<?php

use yii\db\Migration;

/**
 * Class m260108_082410_add_press_resources
 */
class m260108_082410_add_press_resources extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Iron Rod
        $this->insert('resource', [
            'resource_id' => 204,
            'name' => 'Iron Rod',
            'type' => 'crafted',
            'icon_url' => 'iron_rod.svg',
        ]);

        // Copper Rod
        $this->insert('resource', [
            'resource_id' => 205,
            'name' => 'Copper Rod',
            'type' => 'crafted',
            'icon_url' => 'copper_rod.svg',
        ]);

        // Bolt (universal, can be made from any rod + screw)
        $this->insert('resource', [
            'resource_id' => 206,
            'name' => 'Bolt',
            'type' => 'crafted',
            'icon_url' => 'bolt.svg',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('resource', ['resource_id' => 206]);
        $this->delete('resource', ['resource_id' => 205]);
        $this->delete('resource', ['resource_id' => 204]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260108_082410_add_press_resources cannot be reverted.\n";

        return false;
    }
    */
}
