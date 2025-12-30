<?php

namespace services\behaviors;

use models\EntityType;

/**
 * Factory for creating entity behaviors based on entity type
 *
 * Maps entity_type.type values to behavior classes
 */
class EntityBehaviorFactory
{
    /**
     * Mapping of entity type to behavior class
     */
    private const TYPE_BEHAVIORS = [
        'mining'      => MiningEntityBehavior::class,
        'building'    => DefaultEntityBehavior::class,
        'transporter' => DefaultEntityBehavior::class,
        'manipulator' => DefaultEntityBehavior::class,
        'tree'        => TreeEntityBehavior::class,
        'relief'      => ReliefEntityBehavior::class,
        'resource'    => ResourceEntityBehavior::class,
        'eye'         => EyeEntityBehavior::class,
        'ship'        => ShipEntityBehavior::class,
    ];

    /** @var array Cache of behavior instances by entity_type_id */
    private static $cache = [];

    /**
     * Create or get cached behavior for entity type
     *
     * @param EntityType $entityType
     * @return EntityBehavior
     */
    public static function create(EntityType $entityType)
    {
        $typeId = (int) $entityType->entity_type_id;

        if (isset(self::$cache[$typeId])) {
            return self::$cache[$typeId];
        }

        $behaviorClass = self::getBehaviorClassByTypeId($typeId, $entityType->type);
        self::$cache[$typeId] = new $behaviorClass($entityType);

        return self::$cache[$typeId];
    }

    /**
     * Create behavior by entity type ID
     *
     * @param int $entityTypeId
     * @return EntityBehavior|null
     */
    public static function createById($entityTypeId)
    {
        if (isset(self::$cache[$entityTypeId])) {
            return self::$cache[$entityTypeId];
        }

        $entityType = EntityType::findOne($entityTypeId);
        if (!$entityType) {
            return null;
        }

        return self::create($entityType);
    }

    /**
     * Get behavior class by entity type ID
     * Checks for specific IDs first, then falls back to type-based mapping
     *
     * @param int $typeId
     * @param string $type
     * @return string
     */
    private static function getBehaviorClassByTypeId($typeId, $type)
    {
        // Deposit-based extraction buildings (sawmill, stone quarry, ore drill, mine, quarry)
        // Sawmills: 500-502, Stone Quarries: 503-505, Large Ore Drill: 506
        // Mines: 507-509, Quarries: 510-512
        // Also Ore Drills: 102, 108 (updated to use deposits)
        if (($typeId >= 500 && $typeId <= 512) || in_array($typeId, [102, 108, 506])) {
            return DepositEntityBehavior::class;
        }

        // Fall back to type-based mapping
        return self::getBehaviorClass($type);
    }

    /**
     * Get behavior class for type string
     *
     * @param string $type
     * @return string
     */
    private static function getBehaviorClass($type)
    {
        return self::TYPE_BEHAVIORS[$type] ?? DefaultEntityBehavior::class;
    }

    /**
     * Get all behavior info for client-side
     *
     * @return array Map of entity_type_id => behavior info
     */
    public static function getAllClientBehaviors()
    {
        $behaviors = [];

        $entityTypes = EntityType::find()->all();
        foreach ($entityTypes as $entityType) {
            $behavior = self::create($entityType);
            $behaviors[(int) $entityType->entity_type_id] = $behavior->getClientInfo();
        }

        return $behaviors;
    }

    /**
     * Get all mining type entity IDs
     *
     * @return array
     */
    public static function getMiningEntityTypes()
    {
        return EntityType::find()
            ->select('entity_type_id')
            ->where(['type' => 'mining'])
            ->column();
    }

    /**
     * Get all resource type entity IDs
     *
     * @return array
     */
    public static function getResourceEntityTypes()
    {
        return EntityType::find()
            ->select('entity_type_id')
            ->where(['type' => 'resource'])
            ->column();
    }

    /**
     * Check if entity type requires target entity for placement
     *
     * @param int $entityTypeId
     * @return bool
     */
    public static function requiresTargetEntity($entityTypeId)
    {
        $behavior = self::createById($entityTypeId);
        return $behavior instanceof MiningEntityBehavior;
    }

    /**
     * Clear cache (useful for testing)
     */
    public static function clearCache()
    {
        self::$cache = [];
    }
}
