<?php

namespace services\behaviors;

use models\Entity;

/**
 * Behavior for relief entities (rocks)
 *
 * Relief entities are:
 * - Not buildable by player (placed by world generation)
 * - Show hover info
 * - Indestructible
 */
class ReliefEntityBehavior extends EntityBehavior
{
    /**
     * Relief cannot be built by player
     */
    public function canBuildAt(int $x, int $y, ?array $visibleTiles = null, ?int $regionId = null): array
    {
        return [
            'allowed' => false,
            'error' => 'Relief cannot be placed by player',
            'targetEntity' => null,
        ];
    }

    /**
     * Relief shows hover info
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Relief is indestructible
     */
    public function isIndestructible(): bool
    {
        return true;
    }

    /**
     * Get client info for relief
     */
    public function getClientInfo(): array
    {
        return array_merge(parent::getClientInfo(), [
            'canBuild' => false,
        ]);
    }
}
