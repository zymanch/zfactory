<?php

namespace bl\entity\types;

/**
 * Base class for building entity types (furnace, assembler, drill, etc.)
 */
abstract class BuildingEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'building';
    }

    /**
     * Whether this building produces power
     */
    public function producesPower(): bool
    {
        return $this->power > 0;
    }

    /**
     * Whether this building consumes power
     */
    public function consumesPower(): bool
    {
        return $this->power < 0;
    }

    /**
     * Get power production/consumption (positive = production)
     */
    public function getPowerValue(): int
    {
        return $this->power ?? 0;
    }
}
