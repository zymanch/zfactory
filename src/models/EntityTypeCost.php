<?php

namespace models;

use yii\db\ActiveRecord;

/**
 * EntityTypeCost model
 *
 * @property int $entity_type_cost_id
 * @property int $entity_type_id
 * @property int $resource_id
 * @property int $quantity
 *
 * @property EntityType $entityType
 * @property Resource $resource
 */
class EntityTypeCost extends ActiveRecord
{
    public static function tableName()
    {
        return 'entity_type_cost';
    }

    public function rules()
    {
        return [
            [['entity_type_id', 'resource_id', 'quantity'], 'required'],
            [['entity_type_id', 'resource_id', 'quantity'], 'integer'],
        ];
    }

    public function getEntityType()
    {
        return $this->hasOne(EntityType::class, [
            'entity_type_id' => 'entity_type_id'
        ]);
    }

    public function getResource()
    {
        return $this->hasOne(Resource::class, [
            'resource_id' => 'resource_id'
        ]);
    }

    /**
     * Get costs for entity type
     *
     * @param int $entityTypeId
     * @return EntityTypeCost[]
     */
    public static function getCostsForType($entityTypeId)
    {
        return self::find()
            ->where(['entity_type_id' => $entityTypeId])
            ->with('resource')
            ->all();
    }

    /**
     * Check if user can afford building
     *
     * @param int $userId
     * @param int $entityTypeId
     * @return bool
     */
    public static function canAfford($userId, $entityTypeId)
    {
        $costs = self::getCostsForType($entityTypeId);

        foreach ($costs as $cost) {
            if (!UserResource::hasEnough($userId, $cost->resource_id, $cost->quantity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Deduct building cost from user resources
     *
     * @param int $userId
     * @param int $entityTypeId
     * @return bool
     * @throws \Exception if not enough resources
     */
    public static function deductCost($userId, $entityTypeId)
    {
        $costs = self::getCostsForType($entityTypeId);

        foreach ($costs as $cost) {
            if (!UserResource::deductResource($userId, $cost->resource_id, $cost->quantity)) {
                throw new \Exception("Not enough " . $cost->resource->name);
            }
        }

        return true;
    }

    /**
     * Refund building cost to user (when deleting blueprint)
     *
     * @param int $userId
     * @param int $entityTypeId
     * @return bool
     */
    public static function refundCost($userId, $entityTypeId)
    {
        $costs = self::getCostsForType($entityTypeId);

        foreach ($costs as $cost) {
            UserResource::addResource($userId, $cost->resource_id, $cost->quantity);
        }

        return true;
    }
}
