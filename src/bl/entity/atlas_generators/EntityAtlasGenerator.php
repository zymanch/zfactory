<?php

namespace bl\entity\atlas_generators;

use bl\entity\atlas_generators\base\AtlasGeneratorInterface;
use bl\entity\sprite_generators\base\ImageProcessor;

/**
 * Generate texture atlases for standard entity sprites
 *
 * Atlas layout (2 rows):
 * Row 1: States (7 sprites) - normal, damaged, blueprint, normal_selected, damaged_selected, deleting, crafting
 * Row 2: Construction frames (9 frames) - 10%, 20%, ..., 90%
 *
 * Works entirely with GD resources (NO file I/O).
 * Caller provides sprite GD resource and handles saving.
 */
class EntityAtlasGenerator implements AtlasGeneratorInterface
{
    private $spriteGd;    // Source sprite (sprite.png as GD resource)
    private $widthTiles;  // Entity width in tiles
    private $heightTiles; // Entity height in tiles
    private $tileWidth = 64;
    private $tileHeight = 64;

    // Row 1: States to include in atlas (7 sprites)
    private $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected', 'deleting', 'crafting'];

    // Row 2: Construction progress percentages (9 frames)
    private $constructionPercentages = [10, 20, 30, 40, 50, 60, 70, 80, 90];

    /**
     * @param resource $spriteGd Source sprite (sprite.png) as GD resource
     * @param int $widthTiles Entity width in tiles
     * @param int $heightTiles Entity height in tiles
     */
    public function __construct($spriteGd, int $widthTiles, int $heightTiles)
    {
        $this->spriteGd = $spriteGd;
        $this->widthTiles = $widthTiles;
        $this->heightTiles = $heightTiles;
    }

    /**
     * Generate atlas as GD resource
     * @return resource GD image resource of atlas
     */
    public function generate()
    {
        $pixelWidth = $this->widthTiles * $this->tileWidth;
        $pixelHeight = $this->heightTiles * $this->tileHeight;

        // Atlas: 2 rows
        // Row 1: 7 sprites (states)
        // Row 2: 9 construction frames
        $maxSpritesPerRow = max(count($this->states), count($this->constructionPercentages));
        $atlasWidth = $pixelWidth * $maxSpritesPerRow;
        $atlasHeight = $pixelHeight * 2;

        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        imagesavealpha($atlas, true);

        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        // Row 1: State sprites
        $xOffset = 0;
        foreach ($this->states as $state) {
            $stateSprite = $this->generateStateSprite($state);
            imagecopy($atlas, $stateSprite, $xOffset, 0, 0, 0, $pixelWidth, $pixelHeight);
            imagedestroy($stateSprite);
            $xOffset += $pixelWidth;
        }

        // Row 2: Construction frames
        $xOffset = 0;
        $yOffset = $pixelHeight;
        foreach ($this->constructionPercentages as $percent) {
            $constructionSprite = $this->generateConstructionFrame($percent);
            imagecopy($atlas, $constructionSprite, $xOffset, $yOffset, 0, 0, $pixelWidth, $pixelHeight);
            imagedestroy($constructionSprite);
            $xOffset += $pixelWidth;
        }

        return $atlas;
    }

    /**
     * Get atlas dimensions
     * @return array{width: int, height: int}
     */
    public function getDimensions(): array
    {
        $pixelWidth = $this->widthTiles * $this->tileWidth;
        $pixelHeight = $this->heightTiles * $this->tileHeight;
        $maxSpritesPerRow = max(count($this->states), count($this->constructionPercentages));

        return [
            'width' => $pixelWidth * $maxSpritesPerRow,
            'height' => $pixelHeight * 2
        ];
    }

    /**
     * Generate sprite for specific state
     * @param string $state State name
     * @return resource GD resource
     */
    private function generateStateSprite(string $state)
    {
        switch ($state) {
            case 'normal':
                return ImageProcessor::cloneImage($this->spriteGd);

            case 'damaged':
                return ImageProcessor::createDamaged($this->spriteGd);

            case 'blueprint':
                return ImageProcessor::createBlueprint($this->spriteGd);

            case 'normal_selected':
                return ImageProcessor::createSelected($this->spriteGd);

            case 'damaged_selected':
                $damaged = ImageProcessor::createDamaged($this->spriteGd);
                $result = ImageProcessor::createSelected($damaged);
                imagedestroy($damaged);
                return $result;

            case 'deleting':
                // Red tint
                return ImageProcessor::applyColorTint($this->spriteGd, 255, 0, 0);

            case 'crafting':
                // Green tint
                return ImageProcessor::applyColorTint($this->spriteGd, 0, 255, 100);

            default:
                return ImageProcessor::cloneImage($this->spriteGd);
        }
    }

    /**
     * Generate construction progress frame
     * @param int $percent Progress percentage (10-90)
     * @return resource GD resource
     */
    private function generateConstructionFrame(int $percent)
    {
        $width = imagesx($this->spriteGd);
        $height = imagesy($this->spriteGd);

        $frame = imagecreatetruecolor($width, $height);
        imagealphablending($frame, false);
        imagesavealpha($frame, true);

        $transparent = imagecolorallocatealpha($frame, 0, 0, 0, 127);
        imagefill($frame, 0, 0, $transparent);

        // Get blueprint version
        $blueprint = ImageProcessor::createBlueprint($this->spriteGd);

        // Split line (from bottom to top)
        // At 10%, show 10% normal (bottom) + 90% blueprint (top)
        $splitY = (int)($height * (100 - $percent) / 100);

        // Top part: blueprint
        if ($splitY > 0) {
            imagecopy($frame, $blueprint, 0, 0, 0, 0, $width, $splitY);
        }

        // Bottom part: normal
        if ($splitY < $height) {
            imagecopy($frame, $this->spriteGd, 0, $splitY, 0, $splitY, $width, $height - $splitY);
        }

        imagedestroy($blueprint);

        return $frame;
    }
}
