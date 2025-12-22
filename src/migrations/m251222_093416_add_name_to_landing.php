<?php

use yii\db\Migration;

/**
 * Class m251222_093416_add_name_to_landing
 */
class m251222_093416_add_name_to_landing extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add name column
        $this->addColumn('landing', 'name', $this->string(64)->notNull()->after('landing_id'));

        // Update names for existing landings
        $this->update('landing', ['name' => 'Grass'], ['landing_id' => 1]);
        $this->update('landing', ['name' => 'Dirt'], ['landing_id' => 2]);
        $this->update('landing', ['name' => 'Sand'], ['landing_id' => 3]);
        $this->update('landing', ['name' => 'Water'], ['landing_id' => 4]);
        $this->update('landing', ['name' => 'Stone'], ['landing_id' => 5]);
        $this->update('landing', ['name' => 'Lava'], ['landing_id' => 6]);
        $this->update('landing', ['name' => 'Snow'], ['landing_id' => 7]);
        $this->update('landing', ['name' => 'Swamp'], ['landing_id' => 8]);
        $this->update('landing', ['name' => 'Sky'], ['landing_id' => 9]);
        $this->update('landing', ['name' => 'Island Edge'], ['landing_id' => 10]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('landing', 'name');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251222_093416_add_name_to_landing cannot be reverted.\n";

        return false;
    }
    */
}
