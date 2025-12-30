<?php

namespace models;

use yii\db\ActiveRecord;

/**
 * UserResource model
 *
 * @property int $user_resource_id
 * @property int $user_id
 * @property int $resource_id
 * @property int $quantity
 *
 * @property User $user
 * @property Resource $resource
 */
class UserResource extends ActiveRecord
{
    public static function tableName()
    {
        return 'user_resource';
    }

    public function rules()
    {
        return [
            [['user_id', 'resource_id'], 'required'],
            [['user_id', 'resource_id', 'quantity'], 'integer'],
            [['quantity'], 'default', 'value' => 0],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    public function getResource()
    {
        return $this->hasOne(Resource::class, ['resource_id' => 'resource_id']);
    }

    /**
     * Add resources to user (or create record if not exists)
     *
     * @param int $userId
     * @param int $resourceId
     * @param int $quantity
     * @return bool
     */
    public static function addResource($userId, $resourceId, $quantity)
    {
        $userResource = self::findOne([
            'user_id' => $userId,
            'resource_id' => $resourceId
        ]);

        if (!$userResource) {
            $userResource = new self();
            $userResource->user_id = $userId;
            $userResource->resource_id = $resourceId;
            $userResource->quantity = 0;
        }

        $userResource->quantity += $quantity;
        return $userResource->save();
    }

    /**
     * Check if user has enough resources
     *
     * @param int $userId
     * @param int $resourceId
     * @param int $quantity
     * @return bool
     */
    public static function hasEnough($userId, $resourceId, $quantity)
    {
        $userResource = self::findOne([
            'user_id' => $userId,
            'resource_id' => $resourceId
        ]);

        return $userResource && $userResource->quantity >= $quantity;
    }

    /**
     * Deduct resources from user
     *
     * @param int $userId
     * @param int $resourceId
     * @param int $quantity
     * @return bool
     * @throws \Exception if not enough resources
     */
    public static function deductResource($userId, $resourceId, $quantity)
    {
        $userResource = self::findOne([
            'user_id' => $userId,
            'resource_id' => $resourceId
        ]);

        if (!$userResource || $userResource->quantity < $quantity) {
            return false;
        }

        $userResource->quantity -= $quantity;
        return $userResource->save();
    }

    /**
     * Format quantity for UI (3132 → "3к")
     *
     * @return string
     */
    public function getFormatted()
    {
        if ($this->quantity >= 1000) {
            return floor($this->quantity / 1000) . 'к';
        }
        return (string)$this->quantity;
    }
}
