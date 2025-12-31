<?php

namespace bl\entity\types;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\EntityGeneratorFactory;
use models\EntityType;

/**
 * Abstract base class for all EntityType classes
 * Extends the base AR model to add business logic
 */
abstract class AbstractEntityType extends EntityType
{
    /** @var EntityGeneratorFactory */
    private static $generatorFactory;

    /**
     * Get the generator for this entity type
     * @return AbstractEntityGenerator|null
     */
    public function getGenerator(): ?AbstractEntityGenerator
    {
        return self::getGeneratorFactory()->getGenerator($this->image_url);
    }

    /**
     * Get generator factory instance (singleton)
     * @return EntityGeneratorFactory
     */
    protected static function getGeneratorFactory(): EntityGeneratorFactory
    {
        if (self::$generatorFactory === null) {
            self::$generatorFactory = new EntityGeneratorFactory();
        }
        return self::$generatorFactory;
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
