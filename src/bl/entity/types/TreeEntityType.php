<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\tree;

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

    /**
     * Get generator for this tree type
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        $generatorClass = null;

        switch ($this->image_url) {
            case 'tree_pine':
                $generatorClass = tree\PineTreeGenerator::class;
                break;
            case 'tree_oak':
                $generatorClass = tree\OakTreeGenerator::class;
                break;
            case 'tree_dead':
                $generatorClass = tree\DeadTreeGenerator::class;
                break;
            case 'tree_birch':
                $generatorClass = tree\BirchTreeGenerator::class;
                break;
            case 'tree_willow':
                $generatorClass = tree\WillowTreeGenerator::class;
                break;
            case 'tree_maple':
                $generatorClass = tree\MapleTreeGenerator::class;
                break;
            case 'tree_spruce':
                $generatorClass = tree\SpruceTreeGenerator::class;
                break;
            case 'tree_ash':
                $generatorClass = tree\AshTreeGenerator::class;
                break;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }
}
