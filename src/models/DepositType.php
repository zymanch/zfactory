<?php
namespace models;

use bl\deposit\generators\AbstractDepositGenerator;
use bl\entity\generators\base\ImageProcessor;
use models\base;
use bl\deposit;
use Yii;

/**
 * @method AbstractDepositGenerator getGenerator()
 */
class DepositType extends base\BaseDepositType {

    /**
     * Instantiate specific deposit type class based on image_url
     * @return DepositType
     */
    public static function instantiate($row)
    {
        // Map image_url to specific deposit type classes
        $classMap = [
            'ore_iron' => deposit\IronOreDepositType::class,
            'ore_copper' => deposit\CopperOreDepositType::class,
            'ore_aluminum' => deposit\AluminumOreDepositType::class,
            'ore_titanium' => deposit\TitaniumOreDepositType::class,
            'ore_silver' => deposit\SilverOreDepositType::class,
            'ore_gold' => deposit\GoldOreDepositType::class,
        ];

        $className = $classMap[$row['image_url']] ?? null;

        if ($className === null) {
            // Return generic DepositType if no specific class found
            $className = new self;
        }

        // Create instance of specific class and copy attributes
        $instance = new $className();
        $instance->setAttributes($row, false);
        $instance->setIsNewRecord(false);

        return $instance;
    }

    /**
     * Get deposit sprites directory path
     * @return string
     */
    public function getSpritesDir(): string
    {
        $basePath = Yii::getAlias('@app/..');
        return $basePath . '/public/assets/tiles/deposits/' . $this->image_url;
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
        ImageProcessor::createBlueprint($normalPath, $this->getStatePath('blueprint'), $this->image_url);

        // Selected variants
        ImageProcessor::createSelected($normalPath, $this->getStatePath('normal_selected'));
        ImageProcessor::createSelected($this->getStatePath('damaged'), $this->getStatePath('damaged_selected'));

        return true;
    }
}