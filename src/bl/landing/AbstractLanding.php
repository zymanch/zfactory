<?php

namespace bl\landing;

use bl\landing\generators\base\AbstractLandingGenerator;
use bl\landing\generators\LandingGeneratorFactory;
use models\Landing;

/**
 * Abstract base class for all Landing classes
 * Extends the base AR model to add business logic
 */
abstract class AbstractLanding extends Landing
{
    /**
     * Get sprite directory path
     * @return string
     */
    public function getSpriteDir(): string
    {
        return \Yii::getAlias('@app/../public/assets/tiles/landing/' . $this->folder);
    }

    /**
     * Get sprite URL for given variation
     * @param int $variation 0-4 variation index
     * @return string
     */
    public function getSpriteUrl(int $variation = 0): string
    {
        return "/assets/tiles/landing/{$this->folder}/sprite_{$variation}.png";
    }

    /**
     * Whether tiles of this type can have buildings placed on them
     * @return bool
     */
    public function isBuildable(): bool
    {
        return $this->is_buildable === 'yes';
    }

    /**
     * Get random variation index
     * @return int
     */
    public function getRandomVariation(): int
    {
        return mt_rand(0, ($this->variations_count ?: 5) - 1);
    }

    /**
     * Get a generator instance for this landing type
     * @return AbstractLandingGenerator|null
     */
    abstract public function getGenerator(): ?AbstractLandingGenerator;

    /**
     * Whether this is an island terrain (grass, dirt, sand, etc.)
     * @return bool
     */
    public function isIslandTerrain(): bool
    {
        return false;
    }

    /**
     * Whether this is a sky terrain (sky, island_edge)
     * @return bool
     */
    public function isSkyTerrain(): bool
    {
        return false;
    }

    /**
     * Whether this is a ship terrain (ship floors, edges)
     * @return bool
     */
    public function isShipTerrain(): bool
    {
        return false;
    }
}
