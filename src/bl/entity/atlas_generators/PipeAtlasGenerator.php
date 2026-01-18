<?php

namespace bl\entity\atlas_generators;

use bl\entity\atlas_generators\base\AtlasGeneratorInterface;
use bl\entity\sprite_generators\base\ImageProcessor;

/**
 * Generate texture atlases for pipe sprites
 *
 * Atlas layout: 16 variants (X) × 1 row (Y) = 1024×64px (default)
 * - 16 connection variants (4-bit mask: up, down, left, right)
 * - Single row (no animation)
 * - Each tile: 64×64px
 *
 * Works entirely with GD resources (NO file I/O).
 */
class PipeAtlasGenerator implements AtlasGeneratorInterface
{
    const TILE_WIDTH = 64;
    const VARIANTS = 16;  // Connection variants (4-bit: up, down, left, right)

    private $spriteGd;  // sprite.png as GD resource
    private $state;     // normal, damaged, blueprint, normal_selected, damaged_selected

    /**
     * @param resource $spriteGd Sprite as GD resource
     * @param string $state Target state
     */
    public function __construct($spriteGd, string $state)
    {
        $this->spriteGd = $spriteGd;
        $this->state = $state;
    }

    /**
     * Generate atlas as GD resource
     * @return resource GD image resource of atlas
     */
    public function generate()
    {
        $atlasWidth = self::TILE_WIDTH * self::VARIANTS;
        $atlasHeight = self::TILE_WIDTH;  // Single row

        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        imagesavealpha($atlas, true);

        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        // Generate 16 variants
        for ($variant = 0; $variant < self::VARIANTS; $variant++) {
            // For now, use base sprite for all variants
            // TODO: Implement actual variant generation using quarter-triangle algorithm
            $variantSprite = $this->generateVariant($this->spriteGd, $variant);
            $stateSprite = $this->applyState($variantSprite, $this->state);

            $xOffset = $variant * self::TILE_WIDTH;
            imagecopy($atlas, $stateSprite, $xOffset, 0, 0, 0, self::TILE_WIDTH, self::TILE_WIDTH);

            if ($variantSprite !== $this->spriteGd) {
                imagedestroy($variantSprite);
            }
            imagedestroy($stateSprite);
        }

        return $atlas;
    }

    /**
     * Get atlas dimensions
     * @return array{width: int, height: int}
     */
    public function getDimensions(): array
    {
        return [
            'width' => self::TILE_WIDTH * self::VARIANTS,
            'height' => self::TILE_WIDTH
        ];
    }

    /**
     * Generate pipe variant
     * @param resource $baseImage Base sprite
     * @param int $variant Variant index (0-15)
     * @return resource GD resource
     *
     * TODO: Implement quarter-triangle algorithm from PipeController
     */
    private function generateVariant($baseImage, int $variant)
    {
        // For now, just clone base
        // Full implementation would use quarter-triangle composition
        return ImageProcessor::cloneImage($baseImage);
    }

    /**
     * Apply state transformation
     * @param resource $src Source sprite
     * @param string $state State name
     * @return resource Transformed sprite (NEW resource)
     */
    private function applyState($src, string $state)
    {
        switch ($state) {
            case 'normal':
                return ImageProcessor::cloneImage($src);

            case 'damaged':
                return ImageProcessor::createDamaged($src);

            case 'blueprint':
                return ImageProcessor::createBlueprint($src);

            case 'normal_selected':
                return ImageProcessor::createSelected($src);

            case 'damaged_selected':
                $damaged = ImageProcessor::createDamaged($src);
                $result = ImageProcessor::createSelected($damaged);
                imagedestroy($damaged);
                return $result;

            default:
                return ImageProcessor::cloneImage($src);
        }
    }
}
