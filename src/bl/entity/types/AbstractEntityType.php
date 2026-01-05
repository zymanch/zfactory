<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\deposit\generators;
use models\EntityType;

/**
 * Abstract base class for all EntityType classes
 * Extends the base AR model to add business logic
 */
abstract class AbstractEntityType extends EntityType
{
    /**
     * Get the generator for this entity type
     * Default implementation for deposits (no category subclass)
     * Override in category subclasses (Building, Tree, etc.)
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        // Handle deposits (they don't have their own category subclass)
        $generatorClass = null;

        switch ($this->image_url) {
            case 'ore_iron':
                $generatorClass = generators\IronOreGenerator::class;
                break;
            case 'ore_copper':
                $generatorClass = generators\CopperOreGenerator::class;
                break;
            case 'ore_aluminum':
                $generatorClass = generators\AluminumOreGenerator::class;
                break;
            case 'ore_titanium':
                $generatorClass = generators\TitaniumOreGenerator::class;
                break;
            case 'ore_silver':
                $generatorClass = generators\SilverOreGenerator::class;
                break;
            case 'ore_gold':
                $generatorClass = generators\GoldOreGenerator::class;
                break;
        }

        return $generatorClass ? new $generatorClass($this) : null;
    }

    /**
     * Get sprite directory path
     * @return string
     */
    public function getSpriteDir(): string
    {
        return \Yii::getAlias('@app/../public/assets/tiles/entities/' . $this->image_url);
    }

    /**
     * Get sprite URL for given state
     * @param string $state (normal, damaged, blueprint, normal_selected, damaged_selected)
     * @return string
     */
    public function getSpriteUrl(string $state = 'normal'): string
    {
        $extension = $this->extension ?: 'png';
        return "/assets/tiles/entities/{$this->image_url}/{$state}.{$extension}";
    }
}
