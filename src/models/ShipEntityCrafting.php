<?php

namespace models;

use yii\db\ActiveRecord;

/**
 * ShipEntityCrafting model
 *
 * @property int $ship_entity_crafting_id
 * @property int $ship_entity_id
 * @property int $recipe_id
 * @property int $ticks_remaining
 */
class ShipEntityCrafting extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%ship_entity_crafting}}';
    }
}
