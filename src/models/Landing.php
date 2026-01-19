<?php

namespace models;

use bl\landing\LandingFactory;
use models\base;
use Yii;

class Landing extends base\BaseLanding
{
    /**
     * @inheritdoc
     * Creates an instance of the appropriate Landing subclass based on landing_id
     */
    public static function instantiate($row)
    {
        $landingId = $row['landing_id'] ?? null;

        if ($landingId !== null) {
            $class = LandingFactory::getClass((int)$landingId);
            if ($class !== null) {
                return new $class();
            }
        }

        return new static();
    }

    /**
     * Get landing sprites directory path
     * @return string
     */
    public function getSpritesDir(): string
    {
        $basePath = Yii::getAlias('@app/..');
        return $basePath . '/public/assets/tiles/landing/' . $this->folder;
    }

    /**
     * Get path for specific variation sprite
     * @param int $variation 0-4
     * @return string
     */
    public function getVariationPath(int $variation): string
    {
        return $this->getSpritesDir() . '/' . $this->folder . '_' . $variation . '.png';
    }

    /**
     * Get path for original variation sprite (before scaling)
     * @param int $variation 0-4
     * @return string
     */
    public function getOriginalVariationPath(int $variation): string
    {
        return $this->getSpritesDir() . '/' . $this->folder . '_' . $variation . '_original.png';
    }

    /**
     * Save GD resource as variation sprite
     * @param resource $gdImage GD image resource
     * @param int $variation 0-4
     * @return bool
     */
    public function saveVariationSprite($gdImage, int $variation): bool
    {
        $dir = $this->getSpritesDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $this->getVariationPath($variation);
        return imagepng($gdImage, $path, 9);
    }

    /**
     * Get GD resource for variation sprite
     * @param int $variation 0-4
     * @return resource|false
     */
    public function getVariationSprite(int $variation)
    {
        $path = $this->getVariationPath($variation);
        if (!file_exists($path)) {
            return false;
        }
        return imagecreatefrompng($path);
    }

    /**
     * Save GD resource as original variation sprite (before scaling)
     * @param resource $gdImage GD image resource
     * @param int $variation 0-4
     * @return bool
     */
    public function saveOriginalVariationSprite($gdImage, int $variation): bool
    {
        $dir = $this->getSpritesDir();
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $this->getOriginalVariationPath($variation);
        return imagepng($gdImage, $path, 9);
    }

    /**
     * Get GD resource for original variation sprite (before scaling)
     * @param int $variation 0-4
     * @return resource|false
     */
    public function getOriginalVariationSprite(int $variation)
    {
        $path = $this->getOriginalVariationPath($variation);
        if (!file_exists($path)) {
            return false;
        }
        return imagecreatefrompng($path);
    }

    /**
     * Get URL to landing icon (for frontend)
     * Uses first variation (_0.png) as icon
     * @return string
     */
    public function getIconUrl(): string
    {
        return "/assets/tiles/landing/{$this->folder}/{$this->folder}_0.png";
    }
}