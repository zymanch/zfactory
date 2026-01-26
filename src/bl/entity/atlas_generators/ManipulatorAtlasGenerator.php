<?php

namespace bl\entity\atlas_generators;

use bl\entity\atlas_generators\base\AtlasGeneratorInterface;
use bl\entity\sprite_generators\base\ImageProcessor;
use models\EntityType;

/**
 * Generate texture atlases for manipulator sprites with states and construction
 *
 * Atlas layout: 6 rows
 * - Row 0: normal (all frames)
 * - Row 1: damaged (all frames)
 * - Row 2: blueprint (all frames)
 * - Row 3: normal_selected (all frames)
 * - Row 4: damaged_selected (all frames)
 * - Row 5: construction 10%-90% (9 frames horizontally)
 *
 * Width: max(frameCount × totalWidth × 64px, 9 × totalWidth × 64px)
 * Height: 6 rows × totalHeight × 64px (e.g., 6 × 1 tile = 384px)
 *
 * Works entirely with GD resources (NO file I/O).
 * Caller provides animation GD resource (frameCount frames side-by-side).
 */
class ManipulatorAtlasGenerator implements AtlasGeneratorInterface
{
    const TILE_SIZE = 64;

    private $animationGd;      // animation.png as GD resource (multi-frame)
    private $entityType;       // EntityType object
    private $rotationAngle;    // Rotation angle (0, 90, 180, -90)
    private $frameCount;       // Number of animation frames
    private $totalWidth;       // Manipulator width in tiles
    private $totalHeight;      // Manipulator height in tiles

    /**
     * @param resource $animationGd Animation sprite (frameCount frames horizontal) as GD resource
     * @param EntityType $entityType Entity type object
     * @param int $rotationAngle Rotation angle in degrees (0, 90, 180, -90, -180)
     */
    public function __construct($animationGd, EntityType $entityType, int $rotationAngle = 0)
    {
        $this->animationGd = $animationGd;
        $this->entityType = $entityType;
        $this->rotationAngle = $rotationAngle;
        $this->frameCount = (int)$entityType->frame_count;
        $this->totalWidth = (int)$entityType->getTotalWidth();
        $this->totalHeight = (int)$entityType->getTotalHeight();
    }

    /**
     * Generate atlas as GD resource
     * @return resource GD image resource of atlas
     */
    public function generate()
    {
        // Calculate dimensions
        $frameWidth = $this->totalWidth * self::TILE_SIZE;
        $frameHeight = $this->totalHeight * self::TILE_SIZE;

        // Rows 0-4: full animation frames (frameCount frames)
        // Row 5: construction frames (9 frames horizontally)
        $animationWidth = $this->frameCount * $frameWidth;
        $constructionWidth = 9 * $frameWidth;
        $atlasWidth = max($animationWidth, $constructionWidth);
        $atlasHeight = 6 * $frameHeight; // 6 rows (0-5)

        // Create atlas canvas
        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        imagesavealpha($atlas, true);
        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);

        // Extract frames from source animation
        $sourceFrames = $this->extractFrames($this->animationGd);

        // Generate state rows (0-4)
        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
        foreach ($states as $rowIndex => $state) {
            $this->generateStateRow($atlas, $sourceFrames, $state, $rowIndex);
        }

        // Generate construction row (row 5) - 9 frames horizontally
        $centerFrameIndex = $this->getCenterFrameIndex();
        $centerFrame = $sourceFrames[$centerFrameIndex];
        $yOffset = 5 * $frameHeight; // Row 5

        for ($i = 0; $i < 9; $i++) {
            $progress = ($i + 1) * 10; // 10%, 20%, ..., 90%
            $xOffset = $i * $frameWidth; // Horizontal placement

            $this->generateConstructionFrame($atlas, $centerFrame, $progress, $xOffset, $yOffset);
        }

        // Cleanup source frames
        foreach ($sourceFrames as $frame) {
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
        $frameWidth = $this->totalWidth * self::TILE_SIZE;
        $frameHeight = $this->totalHeight * self::TILE_SIZE;

        $animationWidth = $this->frameCount * $frameWidth;
        $constructionWidth = 9 * $frameWidth;

        return [
            'width' => max($animationWidth, $constructionWidth),
            'height' => 6 * $frameHeight // 6 rows (0-5)
        ];
    }

    /**
     * Extract animation frames from source animation
     * @param resource $animationGd
     * @return array Array of GD resources (frames)
     */
    private function extractFrames($animationGd): array
    {
        // Get source animation dimensions (before rotation)
        $sourceWidth = imagesx($animationGd);
        $sourceHeight = imagesy($animationGd);

        // Calculate source frame dimensions
        $sourceFrameWidth = (int)($sourceWidth / $this->frameCount);
        $sourceFrameHeight = $sourceHeight;

        $frames = [];
        for ($i = 0; $i < $this->frameCount; $i++) {
            $x = $i * $sourceFrameWidth;

            // Create frame with source dimensions
            $frame = imagecreatetruecolor($sourceFrameWidth, $sourceFrameHeight);
            imagealphablending($frame, false);
            imagesavealpha($frame, true);
            $transparent = imagecolorallocatealpha($frame, 0, 0, 0, 127);
            imagefill($frame, 0, 0, $transparent);

            imagecopy($frame, $animationGd, 0, 0, $x, 0, $sourceFrameWidth, $sourceFrameHeight);

            // Apply rotation if needed (for orientation variants)
            // After rotation, frame dimensions will change
            if ($this->rotationAngle !== 0) {
                $rotated = ImageProcessor::rotateImage($frame, $this->rotationAngle);
                imagedestroy($frame);
                $frame = $rotated;
            }

            $frames[] = $frame;
        }

        return $frames;
    }

    /**
     * Generate a single state row (rows 0-4)
     * @param resource $atlas Atlas canvas
     * @param array $sourceFrames Source frames (already rotated if needed)
     * @param string $state State name (normal, damaged, blueprint, normal_selected, damaged_selected)
     * @param int $rowIndex Row index (0-4)
     */
    private function generateStateRow($atlas, array $sourceFrames, string $state, int $rowIndex)
    {
        $frameWidth = $this->totalWidth * self::TILE_SIZE;
        $frameHeight = $this->totalHeight * self::TILE_SIZE;
        $yOffset = $rowIndex * $frameHeight;

        foreach ($sourceFrames as $frameIndex => $sourceFrame) {
            // Apply state filter
            $stateFrame = $this->applyState($sourceFrame, $state);

            // Copy to atlas
            $xOffset = $frameIndex * $frameWidth;
            imagecopy($atlas, $stateFrame, $xOffset, $yOffset, 0, 0, $frameWidth, $frameHeight);

            // Cleanup (only if we created a new image)
            if ($stateFrame !== $sourceFrame) {
                imagedestroy($stateFrame);
            }
        }
    }

    /**
     * Generate a single construction frame at specified position
     * @param resource $atlas Atlas canvas
     * @param resource $centerFrame Center frame from source (already rotated if needed)
     * @param int $progress Construction progress (10, 20, ..., 90)
     * @param int $xOffset X offset in atlas
     * @param int $yOffset Y offset in atlas (row index × frame height)
     */
    private function generateConstructionFrame($atlas, $centerFrame, int $progress, int $xOffset, int $yOffset)
    {
        $frameWidth = $this->totalWidth * self::TILE_SIZE;
        $frameHeight = $this->totalHeight * self::TILE_SIZE;

        // Create blueprint version
        $blueprint = ImageProcessor::createBlueprint($centerFrame, $this->entityType->orientation);

        // Calculate split line (progress from bottom to top)
        $splitY = (int)($frameHeight * (100 - $progress) / 100);

        // Create construction frame
        $constructionFrame = imagecreatetruecolor($frameWidth, $frameHeight);
        imagealphablending($constructionFrame, false);
        imagesavealpha($constructionFrame, true);
        $transparent = imagecolorallocatealpha($constructionFrame, 0, 0, 0, 127);
        imagefill($constructionFrame, 0, 0, $transparent);

        // Top part: blueprint
        if ($splitY > 0) {
            imagecopy($constructionFrame, $blueprint, 0, 0, 0, 0, $frameWidth, $splitY);
        }

        // Bottom part: normal
        if ($splitY < $frameHeight) {
            imagecopy($constructionFrame, $centerFrame, 0, $splitY, 0, $splitY, $frameWidth, $frameHeight - $splitY);
        }

        // Copy to atlas at specified position
        imagecopy($atlas, $constructionFrame, $xOffset, $yOffset, 0, 0, $frameWidth, $frameHeight);

        imagedestroy($blueprint);
        imagedestroy($constructionFrame);
    }

    /**
     * Apply state transformation to frame
     * @param resource $src Source frame
     * @param string $state State name
     * @return resource Transformed frame (NEW resource or same if normal)
     */
    private function applyState($src, string $state)
    {
        switch ($state) {
            case 'normal':
                // Return original (no clone needed, we won't modify it)
                return $src;

            case 'damaged':
                return ImageProcessor::createDamaged($src);

            case 'blueprint':
                return ImageProcessor::createBlueprint($src, $this->entityType->orientation);

            case 'normal_selected':
                return ImageProcessor::createSelected($src);

            case 'damaged_selected':
                $damaged = ImageProcessor::createDamaged($src);
                $result = ImageProcessor::createSelected($damaged);
                imagedestroy($damaged);
                return $result;

            default:
                return $src;
        }
    }

    /**
     * Get center frame index
     * @return int
     */
    private function getCenterFrameIndex(): int
    {
        return (int)floor($this->frameCount / 2);
    }
}
