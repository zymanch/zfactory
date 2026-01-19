<?php

use yii\db\Migration;

/**
 * Adds recipe for Stability resource production
 * Recipe 600: 2 Crystal + 1 Steel Plate → 10 Stability (120 ticks = 2 sec at 60fps)
 */
class m260118_100003_add_stability_recipe extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->insert('{{%recipe}}', [
            'recipe_id' => 600,
            'output_resource_id' => 450,  // Stability
            'output_amount' => 10,
            'input1_resource_id' => 108,  // Crystal
            'input1_amount' => 2,
            'input2_resource_id' => 109,  // Steel Plate
            'input2_amount' => 1,
            'ticks' => 120,  // 2 seconds at 60fps
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('{{%recipe}}', ['recipe_id' => 600]);
    }
}
