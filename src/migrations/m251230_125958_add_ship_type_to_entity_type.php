<?php

use yii\db\Migration;

/**
 * Class m251230_125958_add_ship_type_to_entity_type
 */
class m251230_125958_add_ship_type_to_entity_type extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add 'ship' to entity_type.type ENUM
        $this->execute("
            ALTER TABLE {{%entity_type}}
            MODIFY COLUMN `type` ENUM('building','transporter','manipulator','tree','relief','resource','eye','mining','storage','ship')
            NOT NULL
        ");

        // Update ship floor entity types to use 'ship' type
        $this->update('{{%entity_type}}', ['type' => 'ship'], ['entity_type_id' => [600, 601, 602, 603, 604]]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m251230_125958_add_ship_type_to_entity_type cannot be reverted.\n";

        return false;
    }
}
