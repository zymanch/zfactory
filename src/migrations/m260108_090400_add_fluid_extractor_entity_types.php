<?php

use yii\db\Migration;

/**
 * Adds fluid extractor entity types: water, oil, gas, lava pumps
 */
class m260108_090400_add_fluid_extractor_entity_types extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->batchInsert('entity_type',
            ['entity_type_id', 'type', 'name', 'image_url', 'extension', 'max_durability', 'width', 'height', 'icon_url', 'power', 'parent_entity_type_id', 'orientation', 'animation_fps', 'description', 'construction_ticks'],
            [
                // Water Pump (extracts from landing_id=4)
                [145, 'mining', 'Water Pump', 'water_pump', 'png', 200, 2, 2, 'water_pump/normal.png', 100, NULL, 'none', NULL, 'Качает воду из водоёма', 120],

                // Oil Pump (extracts from deposit_type_id=20)
                [146, 'mining', 'Oil Pump', 'oil_pump', 'png', 200, 3, 3, 'oil_pump/normal.png', 100, NULL, 'none', NULL, 'Качает сырую нефть из скважины', 180],

                // Gas Pump (extracts from deposit_type_id=21)
                [147, 'mining', 'Gas Pump', 'gas_pump', 'png', 200, 2, 2, 'gas_pump/normal.png', 150, NULL, 'none', NULL, 'Качает природный газ из месторождения', 120],

                // Lava Pump (extracts from landing_id=6)
                [148, 'mining', 'Lava Pump', 'lava_pump', 'png', 200, 2, 2, 'lava_pump/normal.png', 50, NULL, 'none', NULL, 'Качает магму из лавового озера', 180],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->delete('entity_type', ['entity_type_id' => [145, 146, 147, 148]]);
    }
}
