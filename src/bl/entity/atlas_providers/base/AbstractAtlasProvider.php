<?php

namespace bl\entity\atlas_providers\base;

use bl\entity\sprite_generators\base\ImageProcessor;
use models\EntityType;
use Yii;

/**
 * Abstract base class for atlas providers
 * Provides common functionality for loading sprites and paths
 */
abstract class AbstractAtlasProvider implements AtlasProviderInterface
{
    /** @var string */
    protected $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath ?? Yii::getAlias('@app/..');
    }

    /**
     * Get sprite file path
     * @param EntityType $entityType
     * @param string $filename sprite.png, animation.png, normal.png, etc.
     * @return string Full file path
     */
    protected function getSpritePath(EntityType $entityType, string $filename): string
    {
        $folder = $this->getEntityFolder($entityType);
        return $this->basePath . '/public/assets/tiles/entities/' . $folder . '/' . $filename;
    }

    /**
     * Get entity folder path
     * @param EntityType $entityType
     * @return string Folder path (e.g., "building/furnace", "conveyor/conveyor")
     */
    protected function getEntityFolder(EntityType $entityType): string
    {
        // Structure on disk: type/folder
        // Examples:
        //   building/furnace
        //   conveyor/conveyor
        //   pipe/pipe
        //   electricity/pylon_small
        return $entityType->type . '/' . $entityType->folder;
    }

    /**
     * Load sprite from file as GD resource
     * @param string $path File path
     * @return resource|false GD resource or false on failure
     */
    protected function loadSpriteFromFile(string $path)
    {
        if (!file_exists($path)) {
            return false;
        }

        $ext = pathinfo($path, PATHINFO_EXTENSION);

        switch ($ext) {
            case 'png':
                return imagecreatefrompng($path);
            case 'jpg':
            case 'jpeg':
                return imagecreatefromjpeg($path);
            case 'svg':
                // SVG not supported by GD, would need external library
                return false;
            default:
                return false;
        }
    }

    /**
     * Get rotation angle for orientation
     * @param string|null $orientation up, down, left, right, none
     * @return int Rotation angle (0, 90, 180, 270)
     */
    protected function getRotationAngle(?string $orientation): int
    {
        // NOTE: PHP's imagerotate() uses POSITIVE angles for COUNTER-clockwise rotation
        // These angles match the old ConveyorController (which used -$angle)
        switch ($orientation) {
            case 'up':
                return 90;   // 90° CCW (was -90 in old system, then negated to 90)
            case 'down':
                return -90;  // 90° CW (was 90 in old system, then negated to -90)
            case 'left':
                return -180; // 180° (was 180 in old system, then negated to -180)
            case 'right':
            case 'none':
            default:
                return 0;
        }
    }
}
