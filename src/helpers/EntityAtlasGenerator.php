<?php

namespace helpers;

use models\EntityType;

/**
 * Generate texture atlases for entity sprites
 */
class EntityAtlasGenerator
{
    private $basePath;
    private $tileWidth = 64;
    private $tileHeight = 64;

    // States to include in atlas
    private $states = [
        'normal',
        'damaged',
        'blueprint',
        'normal_selected',
        'damaged_selected'
    ];

    public function __construct($basePath)
    {
        $this->basePath = $basePath;
    }

    /**
     * Generate atlases for all entity types
     */
    public function generateAllAtlases()
    {
        $entities = EntityType::find()->asArray()->all();

        foreach ($entities as $entity) {
            $folder = $entity['image_url'];
            $this->generateAtlasForEntity($folder, $entity['width'], $entity['height']);
        }

        echo "Generated " . count($entities) . " entity atlases.\n";
    }

    /**
     * Generate atlas for single entity type
     */
    private function generateAtlasForEntity($folder, $widthTiles, $heightTiles)
    {
        $entityPath = $this->basePath . '/public/assets/tiles/entities/' . $folder;

        // Check if folder exists
        if (!is_dir($entityPath)) {
            echo "Warning: Entity folder not found: {$folder}\n";
            return;
        }

        $pixelWidth = $widthTiles * $this->tileWidth;
        $pixelHeight = $heightTiles * $this->tileHeight;

        // Atlas will have all states in one row: [normal][damaged][blueprint][normal_selected][damaged_selected]
        $atlasWidth = $pixelWidth * count($this->states);
        $atlasHeight = $pixelHeight;

        // Create atlas image
        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagesavealpha($atlas, true);
        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        // Place each state sprite in atlas
        $xOffset = 0;
        foreach ($this->states as $state) {
            $spritePath = $entityPath . '/' . $state . '.png';

            if (file_exists($spritePath)) {
                $sprite = imagecreatefrompng($spritePath);
                imagesavealpha($sprite, true);

                imagecopy($atlas, $sprite, $xOffset, 0, 0, 0, $pixelWidth, $pixelHeight);
                imagedestroy($sprite);
            } else {
                // If state doesn't exist, fill with transparent
                echo "Warning: Missing {$state}.png for {$folder}\n";
            }

            $xOffset += $pixelWidth;
        }

        // Save atlas
        $atlasPath = $entityPath . '/atlas.png';
        imagepng($atlas, $atlasPath);
        imagedestroy($atlas);

        echo "Generated atlas for {$folder} ({$atlasWidth}x{$atlasHeight})\n";
    }
}
