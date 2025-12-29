<?php

use yii\db\Migration;

/**
 * Class m251229_063809_add_hq_entity_type
 */
class m251229_063809_add_hq_entity_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add 'special' to type ENUM
        $this->execute("ALTER TABLE entity_type MODIFY COLUMN type ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage','special') NOT NULL");

        $this->insert('entity_type', [
            'type' => 'special',
            'name' => 'Headquarters',
            'image_url' => 'hq',
            'extension' => 'png',
            'max_durability' => 1000,
            'width' => 5,
            'height' => 5,
            'icon_url' => null,
            'power' => 0,
            'parent_entity_type_id' => null,
            'orientation' => 'none',
            'description' => 'Main headquarters building that accepts crafted resources and displays accumulated resources in the game header.',
            'construction_ticks' => 100,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('entity_type', ['image_url' => 'hq']);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251229_063809_add_hq_entity_type cannot be reverted.\n";

        return false;
    }
    */
}
