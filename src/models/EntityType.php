<?php

namespace models;

use bl\entity\types\EntityTypeFactory;
use bl\entity\generators\base\ImageProcessor;
use models\base;
use Yii;

class EntityType extends base\BaseEntityType
{
    /**
     * @inheritdoc
     * Creates an instance of the appropriate EntityType subclass based on entity_type_id
     */
    public static function instantiate($row)
    {
        $entityTypeId = $row['entity_type_id'] ?? null;

        if ($entityTypeId !== null) {
            $class = EntityTypeFactory::getClass((int)$entityTypeId);
            if ($class !== null) {
                return new $class();
            }
        }

        return new static();
    }

    /**
     * Get entity sprites directory path
     * @return string
     */
    public function getSpritesDir(): string
    {
        $basePath = Yii::getAlias('@app/..');
        return $basePath . '/public/assets/tiles/entities/' . $this->type . '/' . $this->folder;
    }

    /**
     * Get path for specific state sprite
     * @param string $state 'normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'
     * @return string
     */
    public function getStatePath(string $state): string
    {
        return $this->getSpritesDir() . '/' . $state . '.png';
    }

    /**
     * Save GD resource as normal.png
     * @param resource $gdImage GD image resource
     * @return bool
     */
    public function saveNormalSprite($gdImage): bool
    {
        $dir = $this->getSpritesDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $this->getStatePath('normal');
        return imagepng($gdImage, $path, 9);
    }

    /**
     * Get GD resource for normal sprite
     * @return resource|false
     */
    public function getNormalSprite()
    {
        $path = $this->getStatePath('normal');
        if (!file_exists($path)) {
            return false;
        }
        return imagecreatefrompng($path);
    }

    /**
     * Save GD resource for specific state
     * @param resource $gdImage GD image resource
     * @param string $state 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'
     * @return bool
     */
    public function saveStateSprite($gdImage, string $state): bool
    {
        $dir = $this->getSpritesDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $this->getStatePath($state);
        return imagepng($gdImage, $path, 9);
    }

    /**
     * Get GD resource for specific state
     * @param string $state
     * @return resource|false
     */
    public function getStateSprite(string $state)
    {
        $path = $this->getStatePath($state);
        if (!file_exists($path)) {
            return false;
        }
        return imagecreatefrompng($path);
    }

    /**
     * Generate and save all state variants from normal sprite
     * Uses ImageProcessor to create damaged, blueprint, and selected states
     * @return bool
     */
    public function generateAllStates(): bool
    {
        $normalPath = $this->getStatePath('normal');

        if (!file_exists($normalPath)) {
            return false;
        }

        // Damaged
        ImageProcessor::createDamaged($normalPath, $this->getStatePath('damaged'));

        // Blueprint
        ImageProcessor::createBlueprint($normalPath, $this->getStatePath('blueprint'), $this->folder);

        // Selected variants
        ImageProcessor::createSelected($normalPath, $this->getStatePath('normal_selected'));
        ImageProcessor::createSelected($this->getStatePath('damaged'), $this->getStatePath('damaged_selected'));

        return true;
    }

    /**
     * Get generator for this entity type
     * Delegates to proper subclass via EntityTypeFactory
     * @return \bl\entity\generators\base\AbstractEntityGenerator|null
     */
    public function getGenerator()
    {
        // Get proper subclass instance
        $class = EntityTypeFactory::getClass((int)$this->entity_type_id);
        if ($class === null) {
            return null;
        }

        // Create instance and copy attributes
        $instance = new $class();
        $instance->setAttributes($this->attributes, false);

        // Call getGenerator on proper subclass
        if (method_exists($instance, 'getGenerator')) {
            return $instance->getGenerator();
        }

        return null;
    }
}