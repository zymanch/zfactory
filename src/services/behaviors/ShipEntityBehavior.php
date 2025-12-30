<?php

namespace services\behaviors;

use models\Entity;
use models\Map;
use models\Region;
use Yii;

/**
 * Behavior for ship entity types
 *
 * Placement rules for ships:
 * - Can build where there's NO map (empty space)
 * - Position must be >= region.ship_attach_x/ship_attach_y (within ship bounds)
 * - At least one adjacent tile (4 directions) must have a map (attached to ship)
 * - No entity collision
 * - Must not be in fog of war
 */
class ShipEntityBehavior extends EntityBehavior
{
    /**
     * Check if entity can be built at specified coordinates
     *
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param array|null $visibleTiles Array of visible tile keys
     * @param int|null $regionId Region ID for ship_attach coordinates
     * @return array ['allowed' => bool, 'error' => string|null, 'targetEntity' => null]
     */
    public function canBuildAt(int $tileX, int $tileY, ?array $visibleTiles = null, ?int $regionId = null): array
    {
        // 1. Check fog of war
        if (!$this->areAllTilesVisible($tileX, $tileY, $visibleTiles)) {
            return $this->error('Cannot build in fog of war');
        }

        // 2. Check ship placement rules for all tiles
        if (!$this->checkShipPlacement($tileX, $tileY, $regionId)) {
            return $this->error('Invalid ship placement');
        }

        // 3. Check entity collision (both island and ship entities)
        if ($this->hasEntityCollision($tileX, $tileY)) {
            return $this->error('Position is occupied by another entity');
        }

        // 4. Check ship entity collision (for ship placement)
        if ($this->hasShipEntityCollision($tileX, $tileY, $regionId)) {
            return $this->error('Position is occupied by another ship entity');
        }

        return $this->success();
    }

    /**
     * Check ship placement rules for all tiles
     */
    private function checkShipPlacement(int $tileX, int $tileY, ?int $regionId): bool
    {
        $width = $this->entityType->width ?? 1;
        $height = $this->entityType->height ?? 1;

        // Get region to check ship_attach bounds
        $region = Region::findOne($regionId);
        if (!$region) {
            return false;
        }

        $shipAttachX = $region->ship_attach_x ?? 0;
        $shipAttachY = $region->ship_attach_y ?? 0;

        for ($dx = 0; $dx < $width; $dx++) {
            for ($dy = 0; $dy < $height; $dy++) {
                $checkX = $tileX + $dx;
                $checkY = $tileY + $dy;

                // Check 1: No map of CURRENT region at this position
                // (Ship builds in empty space OR on other regions' islands)
                $map = Map::find()
                    ->where(['x' => $checkX, 'y' => $checkY])
                    ->andWhere(['region_id' => $regionId])
                    ->one();

                if ($map) {
                    return false; // Can't build ship on own island
                }

                // Check 2: Position must be >= ship_attach (within ship bounds)
                if ($checkX < $shipAttachX || $checkY < $shipAttachY) {
                    return false; // Outside ship bounds
                }

                // Check 3: At least one adjacent tile must have a map (attached to ship)
                // Exception: Allow building at ship_attach position (first tile)
                $isShipAttachPosition = ($checkX == $shipAttachX && $checkY == $shipAttachY);
                if (!$isShipAttachPosition && !$this->hasAdjacentMap($checkX, $checkY)) {
                    return false; // Not connected to any existing ship floor
                }
            }
        }

        return true;
    }

    /**
     * Check if there's a ship entity collision at specified position
     */
    private function hasShipEntityCollision(int $tileX, int $tileY, ?int $regionId): bool
    {
        if (!$regionId) {
            return false;
        }

        // Get current user ID
        $userId = \Yii::$app->user->id;
        if (!$userId) {
            return false;
        }

        // Get region to calculate ship-relative coordinates
        $region = Region::findOne($regionId);
        if (!$region) {
            return false;
        }

        $shipAttachX = $region->ship_attach_x ?? 0;
        $shipAttachY = $region->ship_attach_y ?? 0;
        $shipRelativeX = $tileX - $shipAttachX;
        $shipRelativeY = $tileY - $shipAttachY;

        $width = $this->entityType->width ?? 1;
        $height = $this->entityType->height ?? 1;

        // Check for AABB collision with ship entities
        $query = \models\ShipEntity::find()
            ->alias('se')
            ->innerJoin(\models\EntityType::tableName() . ' et', 'se.entity_type_id = et.entity_type_id')
            ->where(['se.user_id' => $userId])
            ->andWhere(['and',
                ['<', 'se.x', $shipRelativeX + $width],
                ['>', new \yii\db\Expression('se.x + COALESCE(et.width, 1)'), $shipRelativeX],
                ['<', 'se.y', $shipRelativeY + $height],
                ['>', new \yii\db\Expression('se.y + COALESCE(et.height, 1)'), $shipRelativeY],
            ]);

        return $query->exists();
    }

    /**
     * Check if at least one adjacent tile (4 directions) has a map or ship entity
     */
    private function hasAdjacentMap(int $tileX, int $tileY): bool
    {
        $adjacentPositions = [
            [$tileX - 1, $tileY],     // Left
            [$tileX + 1, $tileY],     // Right
            [$tileX, $tileY - 1],     // Top
            [$tileX, $tileY + 1],     // Bottom
        ];

        foreach ($adjacentPositions as [$x, $y]) {
            // Check for map tile
            $map = Map::find()
                ->where(['x' => $x, 'y' => $y])
                ->one();

            if ($map) {
                return true; // Found adjacent map
            }

            // Check for ship entity (built or blueprint ship floor)
            $entity = Entity::find()
                ->alias('e')
                ->innerJoin('entity_type et', 'e.entity_type_id = et.entity_type_id')
                ->where(['e.x' => $x, 'e.y' => $y])
                ->andWhere(['et.type' => 'ship'])
                ->one();

            if ($entity) {
                return true; // Found adjacent ship entity
            }
        }

        return false; // No adjacent maps or ship entities
    }

    /**
     * Ship entities show hover info
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Ship entities are destructible
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
