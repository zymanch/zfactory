<?php

namespace bl\landing;

/**
 * Abstract base class for sky-related landings
 * Sky landings are below the floating island (sky background, island edge/stalactites)
 */
abstract class AbstractSkyLanding extends AbstractLanding
{
    /**
     * Sky landings are not island terrain
     * @return bool
     */
    public function isIslandTerrain(): bool
    {
        return false;
    }

    /**
     * Sky landings are sky/edge
     * @return bool
     */
    public function isSkyTerrain(): bool
    {
        return true;
    }

    /**
     * Sky landings are not ship terrains
     * @return bool
     */
    public function isShipTerrain(): bool
    {
        return false;
    }

    /**
     * Sky landings are never buildable
     * @return bool
     */
    public function isBuildable(): bool
    {
        return false;
    }
}
