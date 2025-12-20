<?php

use yii\db\Migration;

/**
 * Adds state and durability fields to entity tables
 */
class m251214_063543_add_entity_state_durability extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add max_durability to entity_type
        $this->addColumn('{{%entity_type}}', 'max_durability', $this->integer()->unsigned()->notNull()->defaultValue(100)->after('image_url'));

        // Add state and durability to entity
        $this->addColumn('{{%entity}}', 'state', "ENUM('built', 'blueprint') NOT NULL DEFAULT 'built' AFTER entity_type_id");
        $this->addColumn('{{%entity}}', 'durability', $this->integer()->unsigned()->notNull()->defaultValue(100)->after('state'));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%entity}}', 'durability');
        $this->dropColumn('{{%entity}}', 'state');
        $this->dropColumn('{{%entity_type}}', 'max_durability');
    }
}
