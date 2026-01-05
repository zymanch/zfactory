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

        // All conveyor variants use ConveyorGenerator
        if (strpos($this->image_url, 'conveyor') === 0) {
            $generatorClass = transporter\ConveyorGenerator::class;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }
}
