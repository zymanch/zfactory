<?php

use yii\db\Migration;

/**
 * Class m251230_081745_add_region_id_to_tables
 */
class m251230_081745_add_region_id_to_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Check if default region exists, if not create it
        $regionExists = $this->db->createCommand('SELECT COUNT(*) FROM {{%region}} WHERE region_id = 1')->queryScalar();
        if (!$regionExists) {
            $this->insert('{{%region}}', [
                'region_id' => 1,
                'name' => 'Starting Island',
                'description' => 'The first region where all players begin their journey',
                'difficulty' => 1,
                'x' => 0,
                'y' => 0,
                'width' => 100,
                'height' => 100,
                'image_url' => 'region_1.png',
            ]);
        }

        // Add region_id to map table (check if not exists)
        $mapColumns = $this->db->getTableSchema('{{%map}}')->columns;
        if (!isset($mapColumns['region_id'])) {
            $this->addColumn('{{%map}}', 'region_id', $this->integer()->unsigned()->notNull()->defaultValue(1)->after('map_id'));
            $this->createIndex('idx_map_region', '{{%map}}', 'region_id');
            $this->addForeignKey('fk_map_region', '{{%map}}', 'region_id', '{{%region}}', 'region_id', 'CASCADE', 'CASCADE');
        }

        // Add region_id to entity table (check if not exists)
        $entityColumns = $this->db->getTableSchema('{{%entity}}')->columns;
        if (!isset($entityColumns['region_id'])) {
            $this->addColumn('{{%entity}}', 'region_id', $this->integer()->unsigned()->notNull()->defaultValue(1)->after('entity_id'));
            $this->createIndex('idx_entity_region', '{{%entity}}', 'region_id');
            $this->addForeignKey('fk_entity_region', '{{%entity}}', 'region_id', '{{%region}}', 'region_id', 'CASCADE', 'CASCADE');
        }

        // Add region_id to deposit table (check if not exists)
        $depositColumns = $this->db->getTableSchema('{{%deposit}}')->columns;
        if (!isset($depositColumns['region_id'])) {
            $this->addColumn('{{%deposit}}', 'region_id', $this->integer()->unsigned()->notNull()->defaultValue(1)->after('deposit_id'));
            $this->createIndex('idx_deposit_region', '{{%deposit}}', 'region_id');
            $this->addForeignKey('fk_deposit_region', '{{%deposit}}', 'region_id', '{{%region}}', 'region_id', 'CASCADE', 'CASCADE');
        }

        // Note: shake_zone table doesn't exist yet, skip it for now
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop foreign keys and columns in reverse order
        $this->dropForeignKey('fk_deposit_region', '{{%deposit}}');
        $this->dropIndex('idx_deposit_region', '{{%deposit}}');
        $this->dropColumn('{{%deposit}}', 'region_id');

        $this->dropForeignKey('fk_entity_region', '{{%entity}}');
        $this->dropIndex('idx_entity_region', '{{%entity}}');
        $this->dropColumn('{{%entity}}', 'region_id');

        $this->dropForeignKey('fk_map_region', '{{%map}}');
        $this->dropIndex('idx_map_region', '{{%map}}');
        $this->dropColumn('{{%map}}', 'region_id');

        // Delete default region
        $this->delete('{{%region}}', ['region_id' => 1]);
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251230_081745_add_region_id_to_tables cannot be reverted.\n";

        return false;
    }
    */
}
