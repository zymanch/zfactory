<?php

use yii\db\Migration;

/**
 * Temporary: Remove oil/gas deposits from region 1 for testing
 */
class m260109_060000_temp_remove_oilgas_deposits extends Migration
{
    public function safeUp()
    {
        $this->delete('deposit', ['deposit_type_id' => [13, 14], 'region_id' => 1]);
    }

    public function safeDown()
    {
        // Add them back
        $this->batchInsert('deposit',
            ['deposit_type_id', 'x', 'y', 'region_id', 'resource_amount'],
            [
                // Oil Wells
                [13, 45, 35, 1, 10000],
                [13, 60, 42, 1, 10000],
                [13, 75, 28, 1, 10000],
                [13, 52, 55, 1, 10000],
                [13, 88, 48, 1, 10000],
                // Gas Vents
                [14, 38, 45, 1, 10000],
                [14, 68, 38, 1, 10000],
                [14, 82, 52, 1, 10000],
                [14, 55, 62, 1, 10000],
                [14, 92, 35, 1, 10000],
            ]
        );
    }
}
