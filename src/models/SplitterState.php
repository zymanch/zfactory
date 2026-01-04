<?php

namespace models;

use Yii;
use yii\db\ActiveRecord;

/**
 * SplitterState model
 * Stores round-robin state for conveyor splitters
 *
 * @property int $splitter_state_id
 * @property int $entity_id
 * @property string $last_output_direction 'left' or 'right'
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Entity $entity
 */
class SplitterState extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%splitter_state}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entity_id'], 'required'],
            [['entity_id'], 'integer'],
            [['last_output_direction'], 'string'],
            [['last_output_direction'], 'in', 'range' => ['left', 'right']],
            [['created_at', 'updated_at'], 'safe'],
            [['entity_id'], 'exist', 'skipOnError' => true, 'targetClass' => Entity::class, 'targetAttribute' => ['entity_id' => 'entity_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'splitter_state_id' => 'Splitter State ID',
            'entity_id' => 'Entity ID',
            'last_output_direction' => 'Last Output Direction',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Entity]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEntity()
    {
        return $this->hasOne(Entity::class, ['entity_id' => 'entity_id']);
    }

    /**
     * Get next output direction and update state
     * @return string 'left' or 'right'
     */
    public function getNextOutputDirection(): string
    {
        // Toggle between left and right
        $next = $this->last_output_direction === 'left' ? 'right' : 'left';
        $this->last_output_direction = $next;
        $this->save(false, ['last_output_direction', 'updated_at']);
        return $next;
    }

    /**
     * Find or create state for entity
     * @param int $entityId
     * @return SplitterState
     */
    public static function findOrCreate(int $entityId): SplitterState
    {
        $state = static::findOne(['entity_id' => $entityId]);

        if (!$state) {
            $state = new static();
            $state->entity_id = $entityId;
            $state->last_output_direction = 'right'; // Start with right
            $state->save();
        }

        return $state;
    }
}
