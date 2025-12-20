<?php

namespace services\behaviors;

use models\Entity;
use models\EntityType;

/**
 * Default behavior for all entity types that don't have special rules
 *
 * Placement rules:
 * - Must not be in fog of war
 * - All tiles must have buildable landing (is_buildable = 'yes')
 * - No entity collision (considering multi-tile entities)
 */
class DefaultEntityBehavior extends EntityBehavior
{
    /**
     * Check if entity can be built at specified coordinates
     *
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param array|null $visibleTiles Array of visible tile keys
     * @return array ['allowed' => bool, 'error' => string|null, 'targetEntity' => null]
     */
    public function canBuildAt(int $tileX, int $tileY, ?array $visibleTiles = null): array
    {
        // 1. Check fog of war
        if (!$this->checkFogOfWar($tileX, $tileY, $visibleTiles)) {
            return $this->error('Cannot build in fog of war');
        }

        // 2. Check landing is buildable
        if (!$this->checkLandingBuildable($tileX, $tileY)) {
            return $this->error('Cannot build on this terrain');
        }

        // 3. Check entity collision
        if (!$this->checkNoCollision($tileX, $tileY)) {
            return $this->error('Position is occupied by another entity');
        }

        return $this->success();
    }

    /**
     * Check fog of war visibility for all tiles
     */
    private function checkFogOfWar(int $tileX, int $tileY, ?array $visibleTiles): bool
    {
        return $this->areAllTilesVisible($tileX, $tileY, $visibleTiles);
    }

    /**
     * Check if all landing tiles are buildable
     */
    private function checkLandingBuildable(int $tileX, int $tileY): bool
    {
        return $this->areAllTilesBuildable($tileX, $tileY);
    }

    /**
     * Check that there's no entity collision
     */
    private function checkNoCollision(int $tileX, int $tileY): bool
    {
        return !$this->hasEntityCollision($tileX, $tileY);
    }

    /**
     * Default entities show hover info
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Default entities are destructible
     */
    public function isIndestructible(): bool
    {
        return false;
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
    private function success(): array
    {
        return [
            'allowed' => true,
            'error' => null,
            'targetEntity' => null,
        ];
    }
}
