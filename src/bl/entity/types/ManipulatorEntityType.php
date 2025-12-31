<?php

namespace bl\entity\types;

/**
 * Base class for manipulator entity types (robotic arms)
 */
abstract class ManipulatorEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'manipulator';
    }

    /**
     * Whether this manipulator has rotational variants
     */
    public function hasRotationalVariants(): bool
    {
        return true;
    }

    /**
     * Get reach distance in tiles
     */
    abstract public function getReachDistance(): int;
}
