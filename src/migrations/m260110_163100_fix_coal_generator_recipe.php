<?php

use yii\db\Migration;

/**
 * Fix Coal Generator recipe - use Coal (4) instead of Crude Oil (7)
 */
class m260110_163100_fix_coal_generator_recipe extends Migration
{
    public function safeUp()
    {
        // Fix Coal Generator recipe - should use Coal (4), not Crude Oil (7)
        $this->update('{{%recipe}}',
            ['input1_resource_id' => 4], // Coal
            ['recipe_id' => 500]
        );

        echo "✓ Coal Generator recipe fixed - now uses Coal (4) instead of Crude Oil (7)\n";
    }

    public function safeDown()
    {
        // Revert to Crude Oil
        $this->update('{{%recipe}}',
            ['input1_resource_id' => 7], // Crude Oil
            ['recipe_id' => 500]
        );

        echo "✓ Reverted Coal Generator recipe to use Crude Oil\n";
    }
}
