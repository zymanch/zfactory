<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\manipulator;

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

    /**
     * Get generator for this manipulator type
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        $generatorClass = null;

        switch ($this->image_url) {
            case 'manipulator_short':
                $generatorClass = manipulator\ShortManipulatorGenerator::class;
                break;
            case 'manipulator_long':
                $generatorClass = manipulator\LongManipulatorGenerator::class;
                break;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }
}
