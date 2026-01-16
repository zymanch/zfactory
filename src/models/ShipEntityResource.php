<?php

namespace models;

use yii\db\ActiveRecord;

/**
 * ShipEntityResource model
 *
 * @property int $ship_entity_resource_id
 * @property int $ship_entity_id
 * @property int $resource_id
 * @property int $amount
 * @property string $status
 * @property int $position_px
 * @property string $from_direction
 * @property string $last_output_direction
 */
class ShipEntityResource extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%ship_entity_resource}}';
    }
}
