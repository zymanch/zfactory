<?php

use yii\db\Migration;

/**
 * Class m260108_171321_allow_null_input_in_recipe
 */
class m260108_171321_allow_null_input_in_recipe extends Migration
{
    public function safeUp()
    {
        // Drop foreign key for input1_resource_id
        $this->dropForeignKey('fk_recipe_input1', 'recipe');

        // Modify column to allow NULL
        $this->alterColumn('recipe', 'input1_resource_id', $this->integer()->unsigned()->null());

        // Re-add foreign key with NULL support
        $this->addForeignKey(
            'fk_recipe_input1',
            'recipe',
            'input1_resource_id',
            'resource',
            'resource_id',
            'RESTRICT',
            'CASCADE'
        );

        // Update Water and Lava pump recipes to have NULL input (infinite extraction)
        $this->update('recipe', ['input1_resource_id' => null, 'input1_amount' => null], [
            'output_resource_id' => [300, 303] // Water and Lava
        ]);
    }

    public function safeDown()
    {
        echo "m260108_171321_allow_null_input_in_recipe cannot be fully reverted.\n";
        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260108_171321_allow_null_input_in_recipe cannot be reverted.\n";

        return false;
    }
    */
}
