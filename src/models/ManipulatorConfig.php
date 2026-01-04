<?php

namespace models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ManipulatorConfig model
 * Stores configuration for filtered and counting manipulators
 *
 * @property int $manipulator_config_id
 * @property int $entity_id
 * @property string $filter_resource_ids JSON array
 * @property int|null $max_transfer_count
 * @property int $current_transfer_count
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Entity $entity
 */
class ManipulatorConfig extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%manipulator_config}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entity_id'], 'required'],
            [['entity_id', 'max_transfer_count', 'current_transfer_count'], 'integer'],
            [['filter_resource_ids'], 'string'],
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
            'manipulator_config_id' => 'Config ID',
            'entity_id' => 'Entity ID',
            'filter_resource_ids' => 'Filter Resource IDs',
            'max_transfer_count' => 'Max Transfer Count',
            'current_transfer_count' => 'Current Transfer Count',
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
     * Get filter resource IDs as array
     * @return array
     */
    public function getFilterResourceIdsArray(): array
    {
        if (empty($this->filter_resource_ids)) {
            return [];
        }

        $decoded = json_decode($this->filter_resource_ids, true);
        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    /**
     * Set filter resource IDs from array
     * @param array $ids
     */
    public function setFilterResourceIdsArray(array $ids)
    {
        $this->filter_resource_ids = json_encode(array_values($ids));
    }

    /**
     * Check if manipulator can transfer a specific resource
     * @param int $resourceId
     * @return bool
     */
    public function canTransfer(int $resourceId): bool
    {
        // Check filter
        $filterIds = $this->getFilterResourceIdsArray();
        if (!empty($filterIds) && !in_array($resourceId, $filterIds)) {
            return false;
        }

        // Check counter limit
        if ($this->max_transfer_count !== null && $this->current_transfer_count >= $this->max_transfer_count) {
            return false;
        }

        return true;
    }

    /**
     * Check if transfer limit has been reached
     * @return bool
     */
    public function hasReachedLimit(): bool
    {
        if ($this->max_transfer_count === null) {
            return false;
        }

        return $this->current_transfer_count >= $this->max_transfer_count;
    }

    /**
     * Increment transfer counter
     * @return bool
     */
    public function incrementCounter(): bool
    {
        if ($this->max_transfer_count === null) {
            return true; // No counter, always succeed
        }

        if ($this->current_transfer_count >= $this->max_transfer_count) {
            return false; // Limit reached
        }

        $this->current_transfer_count++;
        return $this->save(false, ['current_transfer_count', 'updated_at']);
    }

    /**
     * Reset transfer counter
     * @return bool
     */
    public function resetCounter(): bool
    {
        $this->current_transfer_count = 0;
        return $this->save(false, ['current_transfer_count', 'updated_at']);
    }

    /**
     * Find or create config for entity
     * @param int $entityId
     * @return ManipulatorConfig
     */
    public static function findOrCreate(int $entityId): ManipulatorConfig
    {
        $config = static::findOne(['entity_id' => $entityId]);

        if (!$config) {
            $config = new static();
            $config->entity_id = $entityId;
            $config->filter_resource_ids = '[]';
            $config->current_transfer_count = 0;
            $config->save();
        }

        return $config;
    }
}
