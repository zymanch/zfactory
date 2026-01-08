<?php

namespace models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "pipe_system".
 *
 * @property int $pipe_system_id
 * @property int $region_id
 * @property int|null $resource_id
 * @property int $current_amount
 * @property int $max_capacity
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Resource $resource
 * @property PipeSystemMember[] $members
 * @property Entity[] $entities
 */
class PipeSystem extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pipe_system';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['region_id'], 'required'],
            [['region_id', 'resource_id', 'current_amount', 'max_capacity'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * Gets query for [[Resource]].
     */
    public function getResource()
    {
        return $this->hasOne(Resource::class, ['resource_id' => 'resource_id']);
    }

    /**
     * Gets query for [[Members]].
     */
    public function getMembers()
    {
        return $this->hasMany(PipeSystemMember::class, ['pipe_system_id' => 'pipe_system_id']);
    }

    /**
     * Gets query for [[Entities]] through members.
     */
    public function getEntities()
    {
        return $this->hasMany(Entity::class, ['entity_id' => 'entity_id'])
            ->via('members');
    }
}
