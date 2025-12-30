<?php

namespace services\behaviors;

use models\Entity;

/**
 * Behavior for resource entities (Iron Ore, Copper Ore, etc.)
 *
 * Resources are:
 * - Not buildable by player (placed by world generation)
 * - Show hover info
 * - Indestructible (cannot be damaged/destroyed by player)
 */
class ResourceEntityBehavior extends EntityBehavior
{
    /**
     * Resources cannot be built by player
     */
    public function canBuildAt(int $x, int $y, ?array $visibleTiles = null, ?int $regionId = null): array
    {
        return [
            'allowed' => false,
            'error' => 'Resources cannot be placed by player',
            'targetEntity' => null,
        ];
    }

    /**
     * Resources should show hover info (resource amount, etc.)
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Resources are indestructible by player
     */
    public function isIndestructible(): bool
    {
        return true;
    }

    /**
     * Get client info for resources
     */
    public function getClientInfo(): array
    {
        return array_merge(parent::getClientInfo(), [
            'canBuild' => false,
        ]);
    }
}
