<?php

use yii\db\Migration;

/**
 * Добавляет новые ресурсы: алюминий, титан, серебро, золото
 * (deposits и ores)
 */
class m251228_110000_add_new_resources extends Migration
{
    public function safeUp()
    {
        $this->batchInsert('resource', ['resource_id', 'name', 'icon_url', 'type'], [
            // Deposits (неперемещаемые, в залежах)
            [10, 'Aluminum Deposit', 'aluminum_deposit.svg', 'deposit'],
            [11, 'Titanium Deposit', 'titanium_deposit.svg', 'deposit'],
            [12, 'Silver Deposit', 'silver_deposit.svg', 'deposit'],
            [13, 'Gold Deposit', 'gold_deposit.svg', 'deposit'],

            // Ores (перемещаемые, добытые)
            [14, 'Aluminum Ore', 'aluminum_ore.svg', 'raw'],
            [15, 'Titanium Ore', 'titanium_ore.svg', 'raw'],
            [16, 'Silver Ore', 'silver_ore.svg', 'raw'],
            [17, 'Gold Ore', 'gold_ore.svg', 'raw'],
        ]);

        // Добавить новые deposit_type для этих руд
        $this->batchInsert('deposit_type', [
            'deposit_type_id', 'type', 'name', 'description', 'image_url', 'extension',
            'max_durability', 'width', 'height', 'icon_url', 'resource_id', 'resource_amount'
        ], [
            [302, 'ore', 'Aluminum Ore', 'Месторождение алюминиевой руды', 'ore_aluminum', 'png', 9999, 1, 1, 'ore_aluminum/normal.png', 10, 9999],
            [303, 'ore', 'Titanium Ore', 'Месторождение титановой руды', 'ore_titanium', 'png', 9999, 1, 1, 'ore_titanium/normal.png', 11, 9999],
            [304, 'ore', 'Silver Ore', 'Месторождение серебряной руды', 'ore_silver', 'png', 9999, 1, 1, 'ore_silver/normal.png', 12, 9999],
            [305, 'ore', 'Gold Ore', 'Месторождение золотой руды', 'ore_gold', 'png', 9999, 1, 1, 'ore_gold/normal.png', 13, 9999],
        ]);
    }

    public function safeDown()
    {
        $this->delete('deposit_type', ['deposit_type_id' => [302, 303, 304, 305]]);
        $this->delete('resource', ['resource_id' => [10, 11, 12, 13, 14, 15, 16, 17]]);
    }
}
