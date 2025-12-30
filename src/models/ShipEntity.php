<?php
namespace models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ShipEntity ActiveRecord model
 *
 * @property int $ship_entity_id
 * @property int $user_id
 * @property int $entity_type_id
 * @property int $x Ship-relative X coordinate (offset from ship_attach_x)
 * @property int $y Ship-relative Y coordinate (offset from ship_attach_y)
 * @property string $state
 * @property int $durability
 *
 * @property User $user
 * @property EntityType $entityType
 */
class ShipEntity extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ship_entity}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'entity_type_id', 'x', 'y'], 'required'],
            [['user_id', 'entity_type_id', 'x', 'y', 'durability'], 'integer'],
            [['state'], 'string'],
            [['state'], 'in', 'range' => ['built', 'blueprint']],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'user_id']],
            [['entity_type_id'], 'exist', 'skipOnError' => true, 'targetClass' => EntityType::class, 'targetAttribute' => ['entity_type_id' => 'entity_type_id']],
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    /**
     * Gets query for [[EntityType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEntityType()
    {
        return $this->hasOne(EntityType::class, ['entity_type_id' => 'entity_type_id']);
    }
}
