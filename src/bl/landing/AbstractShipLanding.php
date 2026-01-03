<?php

namespace bl\landing;

/**
 * Abstract base class for ship landings
 * Ship landings are terrains on the spaceship (metal floors, edges)
 */
abstract class AbstractShipLanding extends AbstractLanding
{
    /**
     * Ship landings are not island terrain
     * @return bool
     */
    public function isIslandTerrain(): bool
    {
        return false;
    }

    /**
     * Ship landings are not sky/edge
     * @return bool
     */
    public function isSkyTerrain(): bool
    {
        return false;
    }

    /**
     * Ship landings are ship terrains
     * @return bool
     */
    public function isShipTerrain(): bool
    {
        return true;
    }
}
