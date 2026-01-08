<?php

use yii\db\Migration;

/**
 * Class m260108_082418_add_press_cost
 */
class m260108_082418_add_press_cost extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $pressEntityTypeId = 115;

        // 10 Iron Ingots
        $this->insert('entity_type_cost', [
            'entity_type_id' => $pressEntityTypeId,
            'resource_id' => 100, // Iron Ingot
            'quantity' => 10,
        ]);

        // 5 Copper Ingots
        $this->insert('entity_type_cost', [
            'entity_type_id' => $pressEntityTypeId,
            'resource_id' => 101, // Copper Ingot
            'quantity' => 5,
        ]);

        // 20 Stone
        $this->insert('entity_type_cost', [
            'entity_type_id' => $pressEntityTypeId,
            'resource_id' => 5, // Stone
            'quantity' => 20,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('entity_type_cost', ['entity_type_id' => 115]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260108_082418_add_press_cost cannot be reverted.\n";

        return false;
    }
    */
}
