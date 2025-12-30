<?php

use yii\db\Migration;

/**
 * Class m251230_110440_add_from_region_to_user_region_visit
 */
class m251230_110440_add_from_region_to_user_region_visit extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add from_region_id column (nullable - for first region there's no "from")
        $this->addColumn('{{%user_region_visit}}', 'from_region_id',
            $this->integer()->unsigned()->null()->after('region_id')
        );

        // Add foreign key
        $this->addForeignKey(
            'fk_user_region_visit_from_region',
            '{{%user_region_visit}}',
            'from_region_id',
            '{{%region}}',
            'region_id',
            'SET NULL',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key
        $this->dropForeignKey('fk_user_region_visit_from_region', '{{%user_region_visit}}');

        // Drop column
        $this->dropColumn('{{%user_region_visit}}', 'from_region_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251230_110440_add_from_region_to_user_region_visit cannot be reverted.\n";

        return false;
    }
    */
}
