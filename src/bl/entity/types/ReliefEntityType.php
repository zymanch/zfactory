<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\relief;

/**
 * Base class for relief entity types (rocks, stones)
 */
abstract class ReliefEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'relief';
    }

    /**
     * Whether this relief can be mined
     */
    public function isMineable(): bool
    {
        return $this->max_durability > 0;
    }

    /**
     * Get rock size (small, medium, large)
     */
    abstract public function getRockSize(): string;

    /**
     * Get generator for this relief type
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        $generatorClass = null;

        switch ($this->image_url) {
            case 'rock_small':
                $generatorClass = relief\SmallRockGenerator::class;
                break;
            case 'rock_medium':
                $generatorClass = relief\MediumRockGenerator::class;
                break;
            case 'rock_large':
                $generatorClass = relief\LargeRockGenerator::class;
                break;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }
}
