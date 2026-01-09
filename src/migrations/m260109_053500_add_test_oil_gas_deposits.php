<?php

use yii\db\Migration;

/**
 * Class m260109_053500_add_test_oil_gas_deposits
 */
class m260109_053500_add_test_oil_gas_deposits extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Get the default region_id (assuming region_id = 1)
        $regionId = 1;

        // Add Oil Well deposits (deposit_type_id = 13)
        // Placing them in various accessible locations on the map
        $this->batchInsert('deposit',
            ['deposit_type_id', 'x', 'y', 'region_id', 'resource_amount'],
            [
                // Oil Wells - scattered across map
                [13, 45, 35, $regionId, 10000],
                [13, 60, 42, $regionId, 10000],
                [13, 75, 28, $regionId, 10000],
                [13, 52, 55, $regionId, 10000],
                [13, 88, 48, $regionId, 10000],
            ]
        );

        // Add Gas Vent deposits (deposit_type_id = 14)
        $this->batchInsert('deposit',
            ['deposit_type_id', 'x', 'y', 'region_id', 'resource_amount'],
            [
                // Gas Vents - scattered across map
                [14, 38, 45, $regionId, 10000],
                [14, 68, 38, $regionId, 10000],
                [14, 82, 52, $regionId, 10000],
                [14, 55, 62, $regionId, 10000],
                [14, 92, 35, $regionId, 10000],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove test deposits
        $this->delete('deposit', ['deposit_type_id' => [13, 14]]);
    }
}
