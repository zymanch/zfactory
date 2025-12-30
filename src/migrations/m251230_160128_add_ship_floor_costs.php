<?php

use yii\db\Migration;

/**
 * Class m251230_160128_add_ship_floor_costs
 */
class m251230_160128_add_ship_floor_costs extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add costs for ship floor entity types
        $this->batchInsert('entity_type_cost', ['entity_type_id', 'resource_id', 'quantity'], [
            // Wooden Ship Floor (600): 5 Wood
            [600, 1, 5],

            // Iron Ship Floor (601): 2 Iron Plate
            [601, 102, 2],

            // Steel Ship Floor (602): 3 Steel Plate
            [602, 109, 3],

            // Titanium Ship Floor (603): 3 Iron Plate + 2 Titanium Ore
            [603, 102, 3],
            [603, 15, 2],

            // Crystal Ship Floor (604): 5 Crystal + 2 Steel Plate
            [604, 108, 5],
            [604, 109, 2],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('entity_type_cost', ['entity_type_id' => [600, 601, 602, 603, 604]]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251230_160128_add_ship_floor_costs cannot be reverted.\n";

        return false;
    }
    */
}
