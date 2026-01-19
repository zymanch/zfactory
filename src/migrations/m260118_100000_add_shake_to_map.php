<?php

use yii\db\Migration;

/**
 * Adds shake_intensity column to map table and creates test shake zone in region_id=1
 */
class m260118_100000_add_shake_to_map extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add shake_intensity column
        $this->addColumn('{{%map}}', 'shake_intensity',
            $this->decimal(3, 2)->null()->comment('0.00-1.00 shake coefficient, null=no shake'));

        // Create index for performance
        $this->createIndex('idx_map_shake', '{{%map}}', 'shake_intensity');

        // Create test shake zone in region_id=1 (15x15 area with 3 intensity levels)

        // High intensity center (3x3 area, x=25-27, y=25-27): 0.80
        for ($y = 25; $y <= 27; $y++) {
            for ($x = 25; $x <= 27; $x++) {
                $this->update('{{%map}}',
                    ['shake_intensity' => 0.80],
                    ['region_id' => 1, 'x' => $x, 'y' => $y]
                );
            }
        }

        // Medium intensity ring (7x7 area excluding center, x=23-29, y=23-29): 0.50
        for ($y = 23; $y <= 29; $y++) {
            for ($x = 23; $x <= 29; $x++) {
                // Skip high intensity center
                if ($x >= 25 && $x <= 27 && $y >= 25 && $y <= 27) {
                    continue;
                }
                $this->update('{{%map}}',
                    ['shake_intensity' => 0.50],
                    ['region_id' => 1, 'x' => $x, 'y' => $y]
                );
            }
        }

        // Low intensity edge (13x13 area excluding inner zones, x=20-32, y=20-32): 0.30
        for ($y = 20; $y <= 32; $y++) {
            for ($x = 20; $x <= 32; $x++) {
                // Skip medium and high zones
                if ($x >= 23 && $x <= 29 && $y >= 23 && $y <= 29) {
                    continue;
                }
                $this->update('{{%map}}',
                    ['shake_intensity' => 0.30],
                    ['region_id' => 1, 'x' => $x, 'y' => $y]
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropIndex('idx_map_shake', '{{%map}}');
        $this->dropColumn('{{%map}}', 'shake_intensity');
    }
}
