<?php

namespace bl\landing;

/**
 * Abstract base class for island terrain landings
 * Island landings are buildable terrains on the floating island (grass, dirt, sand, etc.)
 */
abstract class AbstractIslandLanding extends AbstractLanding
{
    /**
     * Island landings are generally buildable
     * @return bool
     */
    public function isIslandTerrain(): bool
    {
        return true;
    }

    /**
     * Island landings are not sky/edge
     * @return bool
     */
    public function isSkyTerrain(): bool
    {
        return false;
    }

    /**
     * Island landings are not ship terrains
     * @return bool
     */
    public function isShipTerrain(): bool
    {
        return false;
    }
}
