<?php

use yii\db\Migration;

/**
 * Class m260108_172300_add_oil_gas_deposit_types
 */
class m260108_172300_add_oil_gas_deposit_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Add new deposit types for oil and gas extraction
        // These will appear as deposits on the map for oil/gas pumps to extract from
        $this->batchInsert('deposit_type',
            ['deposit_type_id', 'type', 'name', 'image_url', 'resource_id', 'resource_amount', 'width', 'height'],
            [
                // Oil Well - uses deposit resource 401 (Oil Well)
                [13, 'ore', 'Oil Well', 'oil_well', 401, 10000, 1, 1],
                // Gas Vent - uses deposit resource 402 (Gas Vent)
                [14, 'ore', 'Gas Vent', 'gas_vent', 402, 10000, 1, 1],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Remove deposit types
        $this->delete('deposit_type', ['deposit_type_id' => [13, 14]]);
    }
}
