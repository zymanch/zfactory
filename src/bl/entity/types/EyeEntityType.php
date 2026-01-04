<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\eye;

/**
 * Base class for eye entity types (decorative structures like crystal towers)
 */
abstract class EyeEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'eye';
    }

    /**
     * Whether this is a decorative-only entity
     */
    public function isDecorative(): bool
    {
        return true;
    }

    /**
     * Get generator for this eye type
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        $generatorClass = null;

        switch ($this->image_url) {
            case 'crystal_tower_small':
                $generatorClass = eye\SmallCrystalTowerGenerator::class;
                break;
            case 'crystal_tower_medium':
                $generatorClass = eye\MediumCrystalTowerGenerator::class;
                break;
            case 'crystal_tower_large':
                $generatorClass = eye\LargeCrystalTowerGenerator::class;
                break;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }
}
