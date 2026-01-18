<?php

namespace bl\entity\atlas_generators;

use bl\entity\atlas_generators\base\AtlasGeneratorInterface;
use bl\entity\sprite_generators\base\ImageProcessor;

/**
 * Generate texture atlases for conveyor sprites
 *
 * Atlas layout: 16 variants (X) × 8 frames (Y) = 1024×512px (default)
 * - 16 connection variants (4-bit mask: left, up, right, down)
 * - 8 animation frames
 * - Each tile: 64×64px
 *
 * Works entirely with GD resources (NO file I/O).
 * Caller provides animation GD resource (8 frames side-by-side).
 */
class ConveyorAtlasGenerator implements AtlasGeneratorInterface
{
    const TILE_WIDTH = 64;
    const TILE_HEIGHT = 64;
    const VARIANTS = 16;  // Connection variants
    const FRAMES = 8;     // Animation frames

    private $animationGd;      // animation.png as GD resource (multi-frame)
    private $state;            // Target state (normal, damaged, blueprint, etc.)
    private $orientation;      // right, up, down, left
    private $rotationAngle;    // Rotation angle for each frame (0, 90, 180, 270)

    /**
     * @param resource $animationGd Animation sprite (8 frames horizontal) as GD resource
     * @param string $state Target state (normal, damaged, blueprint, normal_selected, damaged_selected)
     * @param string $orientation Entity orientation (right, up, down, left)
     * @param int $rotationAngle Rotation angle in degrees (0, 90, 180, 270) for per-frame rotation
     */
    public function __construct($animationGd, string $state, string $orientation = 'right', int $rotationAngle = 0)
    {
        $this->animationGd = $animationGd;
        $this->state = $state;
        $this->orientation = $orientation;
        $this->rotationAngle = $rotationAngle;
    }

    /**
     * Generate atlas as GD resource
     * @return resource GD image resource of atlas
     */
    public function generate()
    {
        $atlasWidth = self::TILE_WIDTH * self::VARIANTS;
        $atlasHeight = self::TILE_HEIGHT * self::FRAMES;

        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        imagesavealpha($atlas, true);

        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        // Extract frames from animation
        $frames = $this->extractFrames($this->animationGd);

        // Generate connection variants for each frame
        for ($frameIdx = 0; $frameIdx < self::FRAMES; $frameIdx++) {
            $baseFrame = $frames[$frameIdx];

            for ($variant = 0; $variant < self::VARIANTS; $variant++) {
                // For rotated orientations, we need to determine which BASE variant
                // produces this atlas position when rotated.
                // Old system: loaded base variant X, rotated it, saved as position Y (where Y = rotate(X))
                // New system: for atlas position V, load base variant U (where rotate(U) = V), rotate, place at V

                $sourceVariant = $this->inverseRotateVariantBits($variant);

                // Generate source variant from BASE (right-oriented) frame
                $variantSprite = $this->generateVariant($baseFrame, $sourceVariant);

                // Rotate the generated variant if needed (for orientation variants)
                if ($this->rotationAngle !== 0) {
                    $rotatedVariant = ImageProcessor::rotateImage($variantSprite, $this->rotationAngle);
                    imagedestroy($variantSprite);
                    $variantSprite = $rotatedVariant;
                }

                // Apply state transformation
                $stateSprite = $this->applyState($variantSprite, $this->state);

                // Coordinates in atlas
                $destX = $variant * self::TILE_WIDTH;
                $destY = $frameIdx * self::TILE_HEIGHT;

                imagecopy($atlas, $stateSprite, $destX, $destY, 0, 0, self::TILE_WIDTH, self::TILE_HEIGHT);

                imagedestroy($variantSprite);
                imagedestroy($stateSprite);
            }
        }

        // Cleanup frames
        foreach ($frames as $frame) {
            imagedestroy($frame);
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
            'height' => self::TILE_HEIGHT * self::FRAMES
        ];
    }

    /**
     * Extract 8 animation frames from animation.png
     * Assumes horizontal layout: 8 frames side-by-side
     * DON'T rotate here - rotation happens AFTER variant generation
     * @param resource $animationGd
     * @return array Array of GD resources (frames)
     */
    private function extractFrames($animationGd): array
    {
        $width = imagesx($animationGd);
        $height = imagesy($animationGd);
        $frameWidth = $width / self::FRAMES;

        $frames = [];
        for ($i = 0; $i < self::FRAMES; $i++) {
            $frame = imagecreatetruecolor($frameWidth, $height);
            imagealphablending($frame, false);
            imagesavealpha($frame, true);

            $transparent = imagecolorallocatealpha($frame, 0, 0, 0, 127);
            imagefill($frame, 0, 0, $transparent);

            imagecopy($frame, $animationGd, 0, 0, $i * $frameWidth, 0, $frameWidth, $height);

            $frames[] = $frame;
        }

        return $frames;
    }

    /**
     * Generate connection variant from base frame
     * @param resource $baseFrame Base animation frame
     * @param int $variant Variant index (0-15)
     * @return resource GD resource
     *
     * Variant encoding: left(1), right(2), up(4), down(8)
     * Bit mask: [DOWN][UP][RIGHT][LEFT] = [3][2][1][0]
     */
    private function generateVariant($baseFrame, int $variant)
    {
        // Decode connection flags from variant bitmask
        $left  = $variant & 1;
        $right = $variant & 2;
        $up    = $variant & 4;
        $down  = $variant & 8;

        $width = imagesx($baseFrame);
        $height = imagesy($baseFrame);

        // Create result image
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Fill with transparent
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Copy base frame
        imagecopy($result, $baseFrame, 0, 0, 0, 0, $width, $height);

        // Apply modifications based on connections

        // If no incoming connections - add left transparency
        if (!$left && !$up && !$down) {
            $this->addLeftTransparency($result, 10);
        }

        // If only from top - mirror via anti-diagonal
        if ($up && !$left && !$down) {
            $result = $this->mirrorTriangleTop($result);
        }
        // If top + other neighbors - rotate half
        else if ($up) {
            $this->addTopConnection($result, $baseFrame);
        }

        // If only from bottom - mirror via main diagonal
        if ($down && !$left && !$up) {
            $result = $this->mirrorTriangleBottom($result);
        }
        // If bottom + other neighbors - rotate half
        else if ($down) {
            $this->addBottomConnection($result, $baseFrame);
        }

        return $result;
    }

    /**
     * Add transparent strip on the left
     */
    private function addLeftTransparency($img, $width)
    {
        $height = imagesy($img);
        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                imagesetpixel($img, $x, $y, $transparent);
            }
        }
    }

    /**
     * Add top connection (rotate left half by 90°)
     * For combined cases: LEFT + UP
     */
    private function addTopConnection($result, $baseFrame)
    {
        $width = imagesx($baseFrame);
        $height = imagesy($baseFrame);
        $halfWidth = intval($width / 2);

        // Extract left half
        $leftHalf = imagecreatetruecolor($halfWidth, $height);
        imagealphablending($leftHalf, false);
        imagesavealpha($leftHalf, true);
        imagecopy($leftHalf, $baseFrame, 0, 0, 0, 0, $halfWidth, $height);

        // Rotate 90° (left part becomes top)
        $transparent = imagecolorallocatealpha($leftHalf, 0, 0, 0, 127);
        $rotated = imagerotate($leftHalf, -90, $transparent);
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        // Copy only to transparent areas
        $this->overlayOnTransparent($result, $rotated, 0, 0);

        imagedestroy($leftHalf);
        imagedestroy($rotated);
    }

    /**
     * Add bottom connection (rotate left half by -90°)
     * For combined cases: LEFT + DOWN
     */
    private function addBottomConnection($result, $baseFrame)
    {
        $width = imagesx($baseFrame);
        $height = imagesy($baseFrame);
        $halfWidth = intval($width / 2);

        // Extract left half
        $leftHalf = imagecreatetruecolor($halfWidth, $height);
        imagealphablending($leftHalf, false);
        imagesavealpha($leftHalf, true);
        imagecopy($leftHalf, $baseFrame, 0, 0, 0, 0, $halfWidth, $height);

        // Rotate -90° = 270° (left part becomes bottom)
        $transparent = imagecolorallocatealpha($leftHalf, 0, 0, 0, 127);
        $rotated = imagerotate($leftHalf, -270, $transparent);
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        // Copy only to transparent areas (place at bottom)
        $rotatedHeight = imagesy($rotated);
        $destY = $height - $rotatedHeight;
        $this->overlayOnTransparent($result, $rotated, 0, $destY);

        imagedestroy($leftHalf);
        imagedestroy($rotated);
    }

    /**
     * Copy image only to transparent areas
     */
    private function overlayOnTransparent($dest, $src, $destX, $destY)
    {
        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);

        for ($y = 0; $y < $srcHeight; $y++) {
            for ($x = 0; $x < $srcWidth; $x++) {
                $destPixelX = $destX + $x;
                $destPixelY = $destY + $y;

                // Check bounds
                if ($destPixelX < 0 || $destPixelX >= imagesx($dest) ||
                    $destPixelY < 0 || $destPixelY >= imagesy($dest)) {
                    continue;
                }

                // Check target pixel transparency
                $destColor = imagecolorat($dest, $destPixelX, $destPixelY);
                $destAlpha = ($destColor >> 24) & 0x7F;

                // If target pixel is transparent (alpha > 100)
                if ($destAlpha > 100) {
                    $srcColor = imagecolorat($src, $x, $y);
                    imagesetpixel($dest, $destPixelX, $destPixelY, $srcColor);
                }
            }
        }
    }

    /**
     * Mirror triangle for TOP connection
     * Uses anti-diagonal (from top-right to bottom-left)
     *
     * Logic:
     * - Top-left triangle: mirror by formula newX = y, newY = x
     * - Bottom-right triangle: copy unchanged
     */
    private function mirrorTriangleTop($img)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        // Create new canvas
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Fill with transparent
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Process all pixels
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);

                // Anti-diagonal: x + y = width - 1
                if ($x + $y < $width) {
                    // Top-left triangle - mirror (simple x/y swap)
                    $newX = $y;
                    $newY = $x;

                    if ($newX >= 0 && $newX < $width && $newY >= 0 && $newY < $height) {
                        imagesetpixel($result, $newX, $newY, $color);
                    }
                } else {
                    // Bottom-right triangle - copy unchanged
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        imagedestroy($img);
        return $result;
    }

    /**
     * Mirror triangle for BOTTOM connection
     * Uses main diagonal y = x (from top-left to bottom-right)
     *
     * Logic:
     * - Bottom-left triangle (x < y): mirror by formula
     * - Top-right triangle (x >= y): copy unchanged
     */
    private function mirrorTriangleBottom($img)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        // Create new canvas
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        // Fill with transparent
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);

        // Process all pixels
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $color = imagecolorat($img, $x, $y);

                if ($x < $y) {
                    // Bottom-left triangle - mirror
                    $newX = $width - $y - 1;
                    $newY = $height - $x - 1;

                    if ($newX >= 0 && $newX < $width && $newY >= 0 && $newY < $height) {
                        imagesetpixel($result, $newX, $newY, $color);
                    }
                } else {
                    // Top-right triangle - copy unchanged
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        imagedestroy($img);
        return $result;
    }

    /**
     * Inverse rotate variant bits
     * Given a position in the rotated atlas, find which base variant should be rotated to fill it
     * @param int $rotatedVariant Variant position in rotated atlas
     * @return int Source variant from base orientation
     */
    private function inverseRotateVariantBits(int $rotatedVariant): int
    {
        // If rotation angle is 0 (right orientation), no change
        if ($this->rotationAngle === 0) {
            return $rotatedVariant;
        }

        // For 90° CCW rotation (up orientation, angle=90): INVERSE
        // Forward was: LEFT->DOWN, RIGHT->UP, UP->LEFT, DOWN->RIGHT
        // Inverse: UP in base -> LEFT in rotated, DOWN in base -> RIGHT in rotated,
        //          RIGHT in base -> UP in rotated, LEFT in base -> DOWN in rotated
        if ($this->rotationAngle === 90) {
            return (($rotatedVariant & 0x1) << 2) |  // LEFT in rotated came from UP in base: bit0 -> bit2
                   (($rotatedVariant & 0x2) << 2) |  // RIGHT in rotated came from DOWN in base: bit1 -> bit3
                   (($rotatedVariant & 0x4) >> 1) |  // UP in rotated came from RIGHT in base: bit2 -> bit1
                   (($rotatedVariant & 0x8) >> 3);   // DOWN in rotated came from LEFT in base: bit3 -> bit0
        }

        // For 90° CW rotation (down orientation, angle=-90): INVERSE
        // Forward was: LEFT->UP, RIGHT->DOWN, UP->RIGHT, DOWN->LEFT
        // Inverse: UP->LEFT, DOWN->RIGHT, RIGHT->UP, LEFT->DOWN
        if ($this->rotationAngle === -90) {
            return (($rotatedVariant & 0x1) << 3) |  // bit0 -> bit3 (LEFT -> DOWN)
                   (($rotatedVariant & 0x2) << 1) |  // bit1 -> bit2 (RIGHT -> UP)
                   (($rotatedVariant & 0x4) >> 2) |  // bit2 -> bit0 (UP -> LEFT)
                   (($rotatedVariant & 0x8) >> 2);   // bit3 -> bit1 (DOWN -> RIGHT)
        }

        // For 180° rotation (left orientation, angle=-180): symmetric, so inverse is same as forward
        if ($this->rotationAngle === 180 || $this->rotationAngle === -180) {
            return (($rotatedVariant & 0x1) << 1) |  // bit0 -> bit1 (LEFT -> RIGHT)
                   (($rotatedVariant & 0x2) >> 1) |  // bit1 -> bit0 (RIGHT -> LEFT)
                   (($rotatedVariant & 0x4) << 1) |  // bit2 -> bit3 (UP -> DOWN)
                   (($rotatedVariant & 0x8) >> 1);   // bit3 -> bit2 (DOWN -> UP)
        }

        // Unknown rotation, return as-is
        return $rotatedVariant;
    }

    /**
     * Apply state transformation (damaged, blueprint, selected)
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
                return ImageProcessor::createBlueprint($src, $this->orientation);

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

    /**
     * Rotate variant bits according to orientation
     * When sprite is rotated, connections also rotate
     *
     * Bit mask: [DOWN][UP][RIGHT][LEFT] = [3][2][1][0]
     *
     * @param int $variant Original variant (0-15)
     * @return int Rotated variant
     */
    private function rotateVariantBits(int $variant): int
    {
        // If rotation angle is 0 (right orientation), no change
        if ($this->rotationAngle === 0) {
            return $variant;
        }

        // For 90° CCW rotation (up orientation, angle=90): RIGHT -> UP
        // LEFT->DOWN, RIGHT->UP, UP->LEFT, DOWN->RIGHT
        if ($this->rotationAngle === 90) {
            return (($variant & 0x1) << 3) |  // bit0 -> bit3 (LEFT -> DOWN)
                   (($variant & 0x2) << 1) |  // bit1 -> bit2 (RIGHT -> UP)
                   (($variant & 0x4) >> 2) |  // bit2 -> bit0 (UP -> LEFT)
                   (($variant & 0x8) >> 2);   // bit3 -> bit1 (DOWN -> RIGHT)
        }

        // For 90° CW rotation (down orientation, angle=-90): RIGHT -> DOWN
        // LEFT->UP, RIGHT->DOWN, UP->RIGHT, DOWN->LEFT
        if ($this->rotationAngle === -90) {
            return (($variant & 0x1) << 2) |  // bit0 -> bit2 (LEFT -> UP)
                   (($variant & 0x2) << 2) |  // bit1 -> bit3 (RIGHT -> DOWN)
                   (($variant & 0x4) >> 1) |  // bit2 -> bit1 (UP -> RIGHT)
                   (($variant & 0x8) >> 3);   // bit3 -> bit0 (DOWN -> LEFT)
        }

        // For 180° rotation (left orientation, angle=-180): LEFT<->RIGHT, UP<->DOWN
        if ($this->rotationAngle === 180 || $this->rotationAngle === -180) {
            return (($variant & 0x1) << 1) |  // bit0 -> bit1 (LEFT -> RIGHT)
                   (($variant & 0x2) >> 1) |  // bit1 -> bit0 (RIGHT -> LEFT)
                   (($variant & 0x4) << 1) |  // bit2 -> bit3 (UP -> DOWN)
                   (($variant & 0x8) >> 1);   // bit3 -> bit2 (DOWN -> UP)
        }

        // Unknown rotation, return as-is
        return $variant;
    }
}
