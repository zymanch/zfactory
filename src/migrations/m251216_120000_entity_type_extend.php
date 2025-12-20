<?php

use yii\db\Migration;

/**
 * Extends entity_type: new types + extension column
 */
class m251216_120000_entity_type_extend extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Extend type enum
        $this->alterColumn('{{%entity_type}}', 'type', "ENUM('building','transporter','manipulator','tree','relief','resource') NOT NULL");

        // Add extension column after image_url
        $this->addColumn('{{%entity_type}}', 'extension', $this->string(4)->notNull()->defaultValue('jpg')->after('image_url'));

        // Set existing records to svg
        $this->update('{{%entity_type}}', ['extension' => 'svg']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%entity_type}}', 'extension');
        $this->alterColumn('{{%entity_type}}', 'type', "ENUM('building','tree','relief') NOT NULL");
    }
}
