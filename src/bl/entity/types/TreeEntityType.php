<?php

namespace bl\entity\types;

/**
 * Base class for tree entity types
 */
abstract class TreeEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'tree';
    }

    /**
     * Whether tree can be harvested for wood
     */
    public function isHarvestable(): bool
    {
        return $this->max_durability > 0;
    }
}
