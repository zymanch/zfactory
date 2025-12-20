<?php

namespace services\behaviors;

use models\Entity;

/**
 * Behavior for tree entities (Pine Tree, Oak Tree, etc.)
 *
 * Trees are:
 * - Not buildable by player (placed by world generation)
 * - Don't show hover info by default
 * - Destructible (can be harvested for wood)
 */
class TreeEntityBehavior extends EntityBehavior
{
    /**
     * Trees cannot be built by player
     */
    public function canBuildAt(int $x, int $y, ?array $visibleTiles = null): array
    {
        return [
            'allowed' => false,
            'error' => 'Trees cannot be placed by player',
            'targetEntity' => null,
        ];
    }

    /**
     * Trees don't show hover info (they're just scenery)
     */
    public function shouldShowHoverInfo(): bool
    {
        return false;
    }

    /**
     * Trees are destructible (can be harvested)
     */
    public function isIndestructible(): bool
    {
        return false;
    }

    /**
     * Get client info for trees
     */
    public function getClientInfo(): array
    {
        return array_merge(parent::getClientInfo(), [
            'canBuild' => false,
        ]);
    }
}
