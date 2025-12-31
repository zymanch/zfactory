<?php

namespace bl\entity\types;

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
}
