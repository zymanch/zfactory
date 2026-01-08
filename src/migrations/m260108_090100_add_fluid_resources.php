<?php

use yii\db\Migration;

/**
 * Adds fluid resources: Water, Crude Oil, Natural Gas, Lava
 */
class m260108_090100_add_fluid_resources extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('resource', ['resource_id', 'name', 'icon_url', 'type'], [
            [300, 'Water', 'water.svg', 'liquid'],
            [301, 'Crude Oil', 'crude_oil.svg', 'liquid'],
            [302, 'Natural Gas', 'natural_gas.svg', 'liquid'],
            [303, 'Lava', 'lava.svg', 'liquid'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('resource', ['resource_id' => [300, 301, 302, 303]]);
    }
}
