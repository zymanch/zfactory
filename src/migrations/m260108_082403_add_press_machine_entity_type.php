<?php

use yii\db\Migration;

/**
 * Class m260108_082403_add_press_machine_entity_type
 */
class m260108_082403_add_press_machine_entity_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('entity_type', [
            'entity_type_id' => 115,
            'type' => 'building',
            'name' => 'Press',
            'description' => 'Press machine for creating intermediate components: rods, plates, wires, screws, and bolts from ingots.',
            'image_url' => 'press',
            'extension' => 'png',
            'max_durability' => 200,
            'width' => 3,
            'height' => 3,
            'icon_url' => 'press/normal.png',
            'construction_ticks' => 300, // 5 seconds
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('entity_type', ['entity_type_id' => 115]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260108_082403_add_press_machine_entity_type cannot be reverted.\n";

        return false;
    }
    */
}
