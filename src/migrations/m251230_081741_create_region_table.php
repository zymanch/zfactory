<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%region}}`.
 */
class m251230_081741_create_region_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%region}}', [
            'region_id' => $this->primaryKey()->unsigned(),
            'name' => $this->string(100)->notNull(),
            'description' => $this->text(),
            'difficulty' => $this->tinyInteger(3)->unsigned()->defaultValue(1),
            'x' => $this->integer()->notNull(),
            'y' => $this->integer()->notNull(),
            'width' => $this->integer()->unsigned()->notNull(),
            'height' => $this->integer()->unsigned()->notNull(),
            'image_url' => $this->string(255),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // Create index on coordinates for spatial queries
        $this->createIndex('idx_region_coordinates', '{{%region}}', ['x', 'y']);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%region}}');
    }
}
