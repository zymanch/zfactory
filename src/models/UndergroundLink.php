<?php

namespace models;

use Yii;
use yii\db\ActiveRecord;

/**
 * UndergroundLink model
 * Links underground conveyor entrance to exit
 *
 * @property int $underground_link_id
 * @property int $entrance_entity_id
 * @property int|null $exit_entity_id
 * @property int $distance Distance in tiles (1-4)
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Entity $entrance
 * @property Entity|null $exit
 */
class UndergroundLink extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%underground_link}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['entrance_entity_id'], 'required'],
            [['entrance_entity_id', 'exit_entity_id', 'distance'], 'integer'],
            [['distance'], 'in', 'range' => [1, 2, 3, 4]],
            [['created_at', 'updated_at'], 'safe'],
            [['entrance_entity_id'], 'exist', 'skipOnError' => true, 'targetClass' => Entity::class, 'targetAttribute' => ['entrance_entity_id' => 'entity_id']],
            [['exit_entity_id'], 'exist', 'skipOnError' => true, 'targetClass' => Entity::class, 'targetAttribute' => ['exit_entity_id' => 'entity_id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'underground_link_id' => 'Link ID',
            'entrance_entity_id' => 'Entrance Entity ID',
            'exit_entity_id' => 'Exit Entity ID',
            'distance' => 'Distance',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Entrance]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEntrance()
    {
        return $this->hasOne(Entity::class, ['entity_id' => 'entrance_entity_id']);
    }

    /**
     * Gets query for [[Exit]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getExit()
    {
        return $this->hasOne(Entity::class, ['entity_id' => 'exit_entity_id']);
    }

    /**
     * Find and link exit for entrance entity
     * Searches for exit in the direction of entrance orientation (1-4 tiles)
     *
     * @param Entity $entranceEntity
     * @return UndergroundLink
     */
    public static function findAndLinkExit(Entity $entranceEntity): UndergroundLink
    {
        // Get entrance orientation from entity_type
        $entranceType = $entranceEntity->entityType;
        $orientation = $entranceType->orientation ?? 'right';

        // Determine search direction (same as orientation)
        $dx = 0;
        $dy = 0;
        switch ($orientation) {
            case 'right': $dx = 1; break;
            case 'left':  $dx = -1; break;
            case 'down':  $dy = 1; break;
            case 'up':    $dy = -1; break;
        }

        // Search for exit at 1-4 tiles distance
        $exitEntity = null;
        $distance = 0;

        for ($d = 1; $d <= 4; $d++) {
            $searchX = $entranceEntity->x + ($dx * $d);
            $searchY = $entranceEntity->y + ($dy * $d);

            // Find entity at this position
            $candidate = Entity::find()
                ->where(['x' => $searchX, 'y' => $searchY])
                ->one();

            if (!$candidate) {
                continue;
            }

            // Check if it's an exit type with same orientation
            $candidateType = $candidate->entityType;
            if (!$candidateType) {
                continue;
            }

            // Check if entity_type_id is in exit ranges (816-819, 824-827, 832-835)
            $typeId = $candidateType->entity_type_id;
            $isExit = ($typeId >= 816 && $typeId <= 819) // Normal out
                   || ($typeId >= 824 && $typeId <= 827) // Dual out
                   || ($typeId >= 832 && $typeId <= 835); // Fast out

            if ($isExit && $candidateType->orientation === $orientation) {
                $exitEntity = $candidate;
                $distance = $d;
                break;
            }
        }

        // Create link (with or without exit)
        $link = new static();
        $link->entrance_entity_id = $entranceEntity->entity_id;
        $link->exit_entity_id = $exitEntity ? $exitEntity->entity_id : null;
        $link->distance = $distance ?: 1;
        $link->save();

        return $link;
    }

    /**
     * Find or create link for entrance entity
     *
     * @param int $entranceEntityId
     * @return UndergroundLink
     */
    public static function findOrCreate(int $entranceEntityId): UndergroundLink
    {
        $link = static::findOne(['entrance_entity_id' => $entranceEntityId]);

        if (!$link) {
            $entranceEntity = Entity::findOne($entranceEntityId);
            if ($entranceEntity) {
                $link = static::findAndLinkExit($entranceEntity);
            } else {
                // Fallback: create empty link
                $link = new static();
                $link->entrance_entity_id = $entranceEntityId;
                $link->distance = 1;
                $link->save();
            }
        }

        return $link;
    }
}
