<?php

use yii\db\Migration;

/**
 * Adds last_output_direction column to entity_resource table
 * This replaces the splitter_state table with a single column in entity_resource
 */
class m260115_040000_add_splitter_state_column extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%entity_resource}}', 'last_output_direction',
            "ENUM('left','right') NULL COMMENT 'Splitter round-robin state'"
        );

        echo "✓ Added last_output_direction column to entity_resource\n";
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%entity_resource}}', 'last_output_direction');

        echo "✓ Removed last_output_direction column from entity_resource\n";
    }
}
