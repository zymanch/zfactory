<?php

use yii\db\Migration;

/**
 * Fix solar panel recipes - remove Sunlight resource, make solar panels generate electricity without input
 */
class m260110_163000_fix_solar_panel_recipes extends Migration
{
    public function safeUp()
    {
        // 1. Update ALL recipes using Sunlight - remove input resource
        // Recipes: 35 (Oil Pump), 501 (Solar Small), 502 (Solar Large)
        $this->update('{{%recipe}}',
            [
                'input1_resource_id' => NULL,
                'input1_amount' => NULL,
            ],
            ['IN', 'recipe_id', [35, 501, 502]]
        );

        // 2. Delete Sunlight resource (no longer needed)
        $this->delete('{{%resource}}', ['resource_id' => 401]);

        echo "✓ Fixed recipes: Oil Pump (35), Solar Panels (501, 502) - removed Sunlight input\n";
        echo "✓ Sunlight resource removed from database\n";
    }

    public function safeDown()
    {
        // 1. Restore Sunlight resource
        $this->insert('{{%resource}}', [
            'resource_id' => 401,
            'name' => 'Sunlight',
            'icon_url' => 'sunlight.png',
            'max_stack' => 1,
        ]);

        // 2. Restore Oil Pump recipe with Sunlight
        $this->update('{{%recipe}}',
            [
                'input1_resource_id' => 401,
                'input1_amount' => 1,
            ],
            ['recipe_id' => 35]
        );

        // 3. Restore solar panel recipes with Sunlight
        $this->update('{{%recipe}}',
            [
                'input1_resource_id' => 401,
                'input1_amount' => 0,
            ],
            ['IN', 'recipe_id', [501, 502]]
        );

        echo "✓ Reverted to Sunlight-based recipes\n";
    }
}
