<?php

use yii\db\Migration;

/**
 * Class m251230_081747_add_ship_fields_to_user
 */
class m251230_081747_add_ship_fields_to_user extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add current_region_id field
        $this->addColumn('{{%user}}', 'current_region_id',
            $this->integer()->unsigned()->notNull()->defaultValue(1)->after('user_id')
        );

        // Add ship characteristics fields
        $this->addColumn('{{%user}}', 'ship_view_radius',
            $this->integer()->unsigned()->notNull()->defaultValue(400)->after('current_region_id')
        );

        $this->addColumn('{{%user}}', 'ship_jump_distance',
            $this->integer()->unsigned()->notNull()->defaultValue(278)->after('ship_view_radius')
        );

        // Create index and foreign key for current_region_id
        $this->createIndex('idx_user_current_region', '{{%user}}', 'current_region_id');
        $this->addForeignKey(
            'fk_user_current_region',
            '{{%user}}',
            'current_region_id',
            '{{%region}}',
            'region_id',
            'RESTRICT',
            'CASCADE'
        );

        // Add user_region_visit record for all existing users (they start in region 1)
        $this->execute("
            INSERT INTO user_region_visit (user_id, region_id, view_radius)
            SELECT user_id, 1, 300
            FROM user
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign key and index
        $this->dropForeignKey('fk_user_current_region', '{{%user}}');
        $this->dropIndex('idx_user_current_region', '{{%user}}');

        // Drop columns
        $this->dropColumn('{{%user}}', 'ship_jump_distance');
        $this->dropColumn('{{%user}}', 'ship_view_radius');
        $this->dropColumn('{{%user}}', 'current_region_id');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251230_081747_add_ship_fields_to_user cannot be reverted.\n";

        return false;
    }
    */
}
