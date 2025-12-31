<?php

namespace bl\entity\types;

/**
 * Base class for transporter entity types (conveyors, etc.)
 */
abstract class TransporterEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'transporter';
    }

    /**
     * Whether this transporter has rotational variants
     */
    public function hasRotationalVariants(): bool
    {
        return true;
    }

    /**
     * Get available orientations
     * @return string[]
     */
    public function getOrientations(): array
    {
        return ['left', 'right', 'up', 'down'];
    }
}
