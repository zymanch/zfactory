<?php

namespace services\behaviors;

use models\Landing;

/**
 * Behavior for Fluid Pump entities (Water Pump, Lava Pump)
 *
 * Placement rules:
 * - Water Pump: must be placed on water (landing with is_water='yes')
 * - Lava Pump: must be placed on lava (landing with is_lava='yes')
 * - Must not be in fog of war
 * - Must not collide with other entities
 */
class FluidPumpEntityBehavior extends EntityBehavior
{
    /**
     * Check if fluid pump can be built at specified coordinates
     *
     * @param int $tileX Tile X coordinate
     * @param int $tileY Tile Y coordinate
     * @param array|null $visibleTiles Array of visible tile keys
     * @param int|null $regionId Region ID (not used)
     * @return array ['allowed' => bool, 'error' => string|null, 'targetEntity' => Entity|null]
     */
    public function canBuildAt(int $tileX, int $tileY, ?array $visibleTiles = null, ?int $regionId = null): array
    {
        // 1. Check fog of war
        if (!$this->areAllTilesVisible($tileX, $tileY, $visibleTiles)) {
            return [
                'allowed' => false,
                'error' => 'Cannot build in fog of war',
                'targetEntity' => null,
            ];
        }

        // 2. Check if landing is correct fluid type
        $fluidCheck = $this->checkFluidLanding($tileX, $tileY);
        if (!$fluidCheck['allowed']) {
            return $fluidCheck;
        }

        // 3. Check entity collision
        if ($this->hasEntityCollision($tileX, $tileY)) {
            return [
                'allowed' => false,
                'error' => 'Position is occupied',
                'targetEntity' => null,
            ];
        }

        return [
            'allowed' => true,
            'error' => null,
            'targetEntity' => null,
        ];
    }

    /**
     * Check if landing at position matches required fluid type
     */
    private function checkFluidLanding(int $tileX, int $tileY): array
    {
        $folder = $this->entityType->folder;
        $requiredFluidType = ($folder === 'water_pump') ? 'water' : 'lava';

        $width = (int) $this->entityType->width ?: 1;
        $height = (int) $this->entityType->height ?: 1;

        // All tiles must be correct fluid type
        for ($dx = 0; $dx < $width; $dx++) {
            for ($dy = 0; $dy < $height; $dy++) {
                $landing = $this->getLandingAt($tileX + $dx, $tileY + $dy);

                if (!$landing) {
                    return [
                        'allowed' => false,
                        'error' => 'No landing found at position',
                        'targetEntity' => null,
                    ];
                }

                if ($landing->fluid_type !== $requiredFluidType) {
                    return [
                        'allowed' => false,
                        'error' => ucfirst($requiredFluidType) . ' pump must be placed on ' . $requiredFluidType,
                        'targetEntity' => null,
                    ];
                }
            }
        }

        return [
            'allowed' => true,
            'error' => null,
            'targetEntity' => null,
        ];
    }

    /**
     * Get landing at tile position
     */
    private function getLandingAt(int $tileX, int $tileY): ?Landing
    {
        // Get landing_id from map table
        $map = \models\Map::find()
            ->where(['x' => $tileX, 'y' => $tileY])
            ->one();

        if (!$map) {
            return null;
        }

        return Landing::findOne($map->landing_id);
    }

    /**
     * Water pump should show hover info
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Water pump is destructible
     */
    public function isIndestructible(): bool
    {
        return false;
    }

    /**
     * Get client info
     */
    public function getClientInfo(): array
    {
        return array_merge(parent::getClientInfo(), [
            'requiresWater' => true,
        ]);
    }
}
