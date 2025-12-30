<?php

namespace services\behaviors;

use models\Entity;
use models\EntityType;

/**
 * Behavior for mining type entities (Mining Drill, Fast Mining Drill)
 *
 * Placement rules:
 * - Must be placed on a resource entity (ore)
 * - Must not be in fog of war
 *
 * Does NOT check:
 * - Landing buildability (mining can be on any terrain where ore is)
 * - Entity collision (ore entity will be replaced)
 */
class MiningEntityBehavior extends EntityBehavior
{
    /** @var array Resource entity type IDs that mining can be placed on */
    private $allowedResourceTypes;

    /**
     * @param EntityType $entityType
     */
    public function __construct(EntityType $entityType)
    {
        parent::__construct($entityType);
        $this->loadAllowedResourceTypes();
    }

    /**
     * Load allowed resource types from database
     */
    private function loadAllowedResourceTypes(): void
    {
        $resourceTypes = EntityType::find()
            ->select('entity_type_id')
            ->where(['type' => 'resource'])
            ->column();

        $this->allowedResourceTypes = array_map('intval', $resourceTypes);
    }

    /**
     * Check if mining entity can be built at specified coordinates
     *
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param array|null $visibleTiles Array of visible tile keys
     * @param int|null $regionId Region ID (not used by mining behavior)
     * @return array ['allowed' => bool, 'error' => string|null, 'targetEntity' => Entity|null]
     */
    public function canBuildAt(int $tileX, int $tileY, ?array $visibleTiles = null, ?int $regionId = null): array
    {
        // Check fog of war first
        if (!$this->checkFogOfWar($tileX, $tileY, $visibleTiles)) {
            return $this->error('Cannot build in fog of war');
        }

        // Check for resource entity at position
        $resourceCheck = $this->checkResourceEntity($tileX, $tileY);
        if (!$resourceCheck['allowed']) {
            return $resourceCheck;
        }

        $resourceEntity = $resourceCheck['targetEntity'];

        // Check for entity collision (excluding the resource entity being replaced)
        if (!$this->checkNoCollision($tileX, $tileY, (int) $resourceEntity->entity_id)) {
            return $this->error('Position is occupied by another entity');
        }

        return $this->success($resourceEntity);
    }

    /**
     * Check that there's no entity collision (excluding target resource)
     */
    private function checkNoCollision(int $tileX, int $tileY, int $excludeEntityId): bool
    {
        return !$this->hasEntityCollision($tileX, $tileY, $excludeEntityId);
    }

    /**
     * Check fog of war visibility
     */
    private function checkFogOfWar(int $tileX, int $tileY, ?array $visibleTiles): bool
    {
        return $this->areAllTilesVisible($tileX, $tileY, $visibleTiles);
    }

    /**
     * Check if there's a valid resource entity at position
     *
     * @return array ['allowed' => bool, 'error' => string|null, 'targetEntity' => Entity|null]
     */
    private function checkResourceEntity(int $tileX, int $tileY): array
    {
        $resourceEntity = $this->findResourceEntityAt($tileX, $tileY);

        if (!$resourceEntity) {
            return $this->error('Requires a resource node (ore) to place mining drill');
        }

        // Check if this resource type is allowed
        if (!$this->isAllowedResourceType((int) $resourceEntity->entity_type_id)) {
            $entityType = EntityType::findOne($resourceEntity->entity_type_id);
            $name = $entityType ? $entityType->name : 'this resource';
            return $this->error("Cannot place mining drill on {$name}");
        }

        return $this->success($resourceEntity);
    }

    /**
     * Check if resource type is allowed for mining placement
     */
    private function isAllowedResourceType(int $entityTypeId): bool
    {
        return in_array($entityTypeId, $this->allowedResourceTypes);
    }

    /**
     * Get allowed resource type IDs
     */
    public function getAllowedResourceTypes(): array
    {
        return $this->allowedResourceTypes;
    }

    /**
     * Mining drills should show hover info
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Mining drills are destructible
     */
    public function isIndestructible(): bool
    {
        return false;
    }

    /**
     * Get client info including allowed target types
     */
    public function getClientInfo(): array
    {
        return array_merge(parent::getClientInfo(), [
            'requiresTarget' => true,
            'allowedTargetTypes' => $this->allowedResourceTypes,
        ]);
    }

    /**
     * Helper: create error response
     */
    private function error(string $message): array
    {
        return [
            'allowed' => false,
            'error' => $message,
            'targetEntity' => null,
        ];
    }

    /**
     * Helper: create success response
     */
    private function success(?Entity $targetEntity): array
    {
        return [
            'allowed' => true,
            'error' => null,
            'targetEntity' => $targetEntity,
        ];
    }
}
