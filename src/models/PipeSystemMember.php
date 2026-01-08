<?php

namespace models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "pipe_system_member".
 *
 * @property int $pipe_system_id
 * @property int $entity_id
 *
 * @property PipeSystem $pipeSystem
 * @property Entity $entity
 */
class PipeSystemMember extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'pipe_system_member';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['pipe_system_id', 'entity_id'], 'required'],
            [['pipe_system_id', 'entity_id'], 'integer'],
        ];
    }

    /**
     * Gets query for [[PipeSystem]].
     */
    public function getPipeSystem()
    {
        return $this->hasOne(PipeSystem::class, ['pipe_system_id' => 'pipe_system_id']);
    }

    /**
     * Gets query for [[Entity]].
     */
    public function getEntity()
    {
        return $this->hasOne(Entity::class, ['entity_id' => 'entity_id']);
    }
}
