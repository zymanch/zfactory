<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\transporter;

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

    /**
     * Get generator for this transporter type
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        $generatorClass = null;

        switch ($this->image_url) {
            case 'conveyor':
                $generatorClass = transporter\ConveyorGenerator::class;
                break;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }
}
