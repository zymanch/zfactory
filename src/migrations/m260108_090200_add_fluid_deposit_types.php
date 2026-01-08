<?php

use yii\db\Migration;

/**
 * Adds deposit types for fluid extraction: Oil Well, Gas Vent
 */
class m260108_090200_add_fluid_deposit_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('deposit_type',
            ['deposit_type_id', 'type', 'name', 'image_url', 'resource_id', 'resource_amount', 'width', 'height'],
            [
                [20, 'ore', 'Oil Well', 'oil_well', 301, 10000, 3, 3],
                [21, 'ore', 'Gas Vent', 'gas_vent', 302, 8000, 2, 2],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('deposit_type', ['deposit_type_id' => [20, 21]]);
    }
}
