<?php

use yii\db\Migration;

/**
 * Class m251230_152458_update_ship_entity_icon_url
 */
class m251230_152458_update_ship_entity_icon_url extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Update icon_url from .svg to .png and type from 'building' to 'ship'
        // for all ship entity types (600-604)
        $shipTypes = [
            600 => 'ship_floor_wood',
            601 => 'ship_floor_iron',
            602 => 'ship_floor_steel',
            603 => 'ship_floor_titanium',
            604 => 'ship_floor_crystal',
        ];

        foreach ($shipTypes as $entityTypeId => $folder) {
            $this->update('{{%entity_type}}',
                [
                    'icon_url' => $folder . '/normal.png',
                    'type' => 'ship',
                ],
                ['entity_type_id' => $entityTypeId]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251230_152458_update_ship_entity_icon_url cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m251230_152458_update_ship_entity_icon_url cannot be reverted.\n";

        return false;
    }
    */
}
