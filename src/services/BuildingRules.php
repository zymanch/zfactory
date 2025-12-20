<?php

namespace services;

use models\Entity;
use models\EntityType;
use services\behaviors\EntityBehaviorFactory;

/**
 * BuildingRules - defines placement rules for buildings
 * Now uses EntityBehavior system for type-specific rules
 */
class BuildingRules
{
    /**
     * Check if building can be placed at position using behavior system
     *
     * @param int $buildingTypeId Entity type ID of building to place
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param array|null $visibleTiles Array of visible tile keys (for fog check)
     * @return array ['allowed' => bool, 'targetEntity' => Entity|null, 'error' => string|null]
     */
    public static function canPlace(
        int $buildingTypeId,
        int $tileX,
        int $tileY,
        ?array $visibleTiles = null
    ): array {
        $behavior = EntityBehaviorFactory::createById($buildingTypeId);

        if (!$behavior) {
            return [
                'allowed' => false,
                'targetEntity' => null,
                'error' => 'Invalid entity type'
            ];
        }

        return $behavior->canBuildAt($tileX, $tileY, $visibleTiles);
    }

    /**
     * Get rules for client-side validation
     * Returns behavior info for all entity types
     *
     * @return array
     */
    public static function getClientRules(): array
    {
        $miningTypes = EntityBehaviorFactory::getMiningEntityTypes();
        $resourceTypes = EntityBehaviorFactory::getResourceEntityTypes();

        // Build requiresTarget map for mining entities
        $requiresTarget = [];
        foreach ($miningTypes as $miningTypeId) {
            $requiresTarget[$miningTypeId] = $resourceTypes;
        }

        return [
            'requiresTarget' => $requiresTarget,
            'resourceEntityTypes' => $resourceTypes,
            'behaviors' => EntityBehaviorFactory::getAllClientBehaviors(),
        ];
    }

    /**
     * Check if entity type is a resource node
     */
    public static function isResourceEntity(int $entityTypeId): bool
    {
        $resourceTypes = EntityBehaviorFactory::getResourceEntityTypes();
        return in_array($entityTypeId, $resourceTypes);
    }

    /**
     * Check if entity type requires target entity
     */
    public static function requiresTargetEntity(int $entityTypeId): bool
    {
        return EntityBehaviorFactory::requiresTargetEntity($entityTypeId);
    }
}
