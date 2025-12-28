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

    // States to include in atlas (row 1: 7 sprites)
    private $states = [
        'normal',
        'damaged',
        'blueprint',
        'normal_selected',
        'damaged_selected',
        'deleting',
        'crafting'
    ];

    // Construction frames (row 2: 9 frames)
    private $constructionFrames = [
        'construction_10',
        'construction_20',
        'construction_30',
        'construction_40',
        'construction_50',
        'construction_60',
        'construction_70',
        'construction_80',
        'construction_90'
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

        // Atlas will have 2 rows:
        // Row 1: 7 sprites (normal, damaged, blueprint, normal_selected, damaged_selected, deleting, crafting)
        // Row 2: 9 construction frames (construction_10 ... construction_90)
        $maxSpritesPerRow = max(count($this->states), count($this->constructionFrames));
        $atlasWidth = $pixelWidth * $maxSpritesPerRow;
        $atlasHeight = $pixelHeight * 2; // 2 rows

        // Create atlas image
        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagesavealpha($atlas, true);
        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        // Row 1: Place each state sprite
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

        // Row 2: Place construction frames
        $xOffset = 0;
        $yOffset = $pixelHeight; // Second row
        foreach ($this->constructionFrames as $frame) {
            $framePath = $entityPath . '/' . $frame . '.png';

            if (file_exists($framePath)) {
                $sprite = imagecreatefrompng($framePath);
                imagesavealpha($sprite, true);

                imagecopy($atlas, $sprite, $xOffset, $yOffset, 0, 0, $pixelWidth, $pixelHeight);
                imagedestroy($sprite);
            } else {
                // If frame doesn't exist, fill with transparent
                echo "Warning: Missing {$frame}.png for {$folder}\n";
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
