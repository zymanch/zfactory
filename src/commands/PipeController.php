<?php

namespace commands;

use yii\console\Controller;

/**
 * Console commands for pipe sprite generation
 */
class PipeController extends Controller
{
    /**
     * Generate 16 pipe connection variants using quarter algorithm
     *
     * Usage: php yii pipe/generate-variants
     */
    public function actionGenerateVariants()
    {
        $basePath = dirname(__DIR__, 2) . '/public/assets/tiles/entities/pipe';
        $sourceFile = $basePath . '/normal.png';

        if (!file_exists($sourceFile)) {
            $this->stderr("Source file not found: $sourceFile\n");
            return 1;
        }

        // Load base horizontal pipe sprite
        $baseImage = imagecreatefrompng($sourceFile);
        if (!$baseImage) {
            $this->stderr("Failed to load base image\n");
            return 1;
        }

        $width = imagesx($baseImage);
        $height = imagesy($baseImage);

        $this->stdout("Base image: {$width}x{$height}\n");

        // Split into 4 triangular sections (from center to corners)
        // Using diagonal lines from center (32,32) to divide sprite
        $centerX = $width / 2;  // 32
        $centerY = $height / 2; // 32

        $triangles = [];

        // Create 4 triangular masks
        // Up triangle: above both diagonals
        $triangles['up'] = $this->extractTriangle($baseImage, $width, $height, 'up');

        // Down triangle: below both diagonals
        $triangles['down'] = $this->extractTriangle($baseImage, $width, $height, 'down');

        // Left triangle: left of both diagonals
        $triangles['left'] = $this->extractTriangle($baseImage, $width, $height, 'left');

        // Right triangle: right of both diagonals
        $triangles['right'] = $this->extractTriangle($baseImage, $width, $height, 'right');

        $this->stdout("Split into 4 triangular sections\n");

        // Variant names
        $variantNames = [
            0 => 'none',
            1 => 'right',
            2 => 'down',
            3 => 'right_down',
            4 => 'left',
            5 => 'horizontal', // left+right
            6 => 'left_down',
            7 => 't_down', // left+right+down
            8 => 'up',
            9 => 'up_right',
            10 => 'vertical', // up+down
            11 => 't_right', // up+right+down
            12 => 'up_left',
            13 => 't_up', // up+left+right
            14 => 't_left', // up+left+down
            15 => 'cross', // all 4
        ];

        // Create variants directory
        $variantsDir = $basePath . '/variants';
        if (!is_dir($variantsDir)) {
            mkdir($variantsDir, 0755, true);
        }

        for ($variant = 0; $variant < 16; $variant++) {
            $variantImage = $this->generateVariant($triangles, $variant, $width, $height);
            $variantName = $variantNames[$variant];
            $outputFile = $variantsDir . "/{$variantName}.png";

            imagepng($variantImage, $outputFile);
            imagedestroy($variantImage);

            $this->stdout("Generated variant {$variant}: {$variantName}.png\n");
        }

        // Cleanup
        foreach ($triangles as $triangle) {
            imagedestroy($triangle);
        }
        imagedestroy($baseImage);

        $this->stdout("\nDone! Generated 16 variants in {$variantsDir}/\n");
        return 0;
    }

    /**
     * Generate atlas from pipe variants (16 variants in one row)
     * Atlas size: 1024x64 (16 variants × 64px each)
     *
     * Usage: php yii pipe/generate-atlas
     */
    public function actionGenerateAtlas()
    {
        $basePath = dirname(__DIR__, 2) . '/public/assets/tiles/entities/pipe';
        $variantsDir = $basePath . '/variants';

        if (!is_dir($variantsDir)) {
            $this->stderr("Variants directory not found. Run generate-variants first.\n");
            return 1;
        }

        $variantNames = [
            'none', 'right', 'down', 'right_down',
            'left', 'horizontal', 'left_down', 't_down',
            'up', 'up_right', 'vertical', 't_right',
            'up_left', 't_up', 't_left', 'cross'
        ];

        // Create atlas (16 variants × 64px = 1024px width, 64px height)
        $atlasWidth = 1024;
        $atlasHeight = 64;
        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);
        imagesavealpha($atlas, true);

        // Copy each variant to atlas
        for ($i = 0; $i < 16; $i++) {
            $variantFile = $variantsDir . '/' . $variantNames[$i] . '.png';

            if (!file_exists($variantFile)) {
                $this->stderr("Variant file not found: {$variantFile}\n");
                continue;
            }

            $variantImage = imagecreatefrompng($variantFile);
            if (!$variantImage) {
                $this->stderr("Failed to load: {$variantFile}\n");
                continue;
            }

            // Copy to atlas at position (i * 64, 0)
            $xOffset = $i * 64;
            imagecopy($atlas, $variantImage, $xOffset, 0, 0, 0, 64, 64);
            imagedestroy($variantImage);

            $this->stdout("Added variant {$i}: {$variantNames[$i]}\n");
        }

        // Save atlas
        $atlasFile = $basePath . '/pipe_atlas.png';
        imagepng($atlas, $atlasFile);
        imagedestroy($atlas);

        $this->stdout("\nAtlas generated: {$atlasFile} ({$atlasWidth}x{$atlasHeight})\n");
        return 0;
    }

    /**
     * Generate atlases for all states (normal, normal_selected, damaged, damaged_selected)
     * Creates 4 atlases from base sprites
     *
     * Usage: php yii pipe/generate-all-atlases
     */
    public function actionGenerateAllAtlases()
    {
        $basePath = dirname(__DIR__, 2) . '/public/assets/tiles/entities/pipe';

        $states = ['normal', 'normal_selected', 'damaged', 'damaged_selected'];

        foreach ($states as $state) {
            $this->stdout("\n=== Generating atlas for state: {$state} ===\n");

            $sourceFile = $basePath . '/' . $state . '.png';
            if (!file_exists($sourceFile)) {
                $this->stderr("Source file not found: {$sourceFile}\n");
                continue;
            }

            // Generate variants for this state
            $variants = $this->generateVariantsForState($sourceFile);

            // Create atlas from variants
            $atlasFile = $this->createAtlasFromVariants($variants, $basePath, $state);

            // Cleanup variant images
            foreach ($variants as $variant) {
                imagedestroy($variant);
            }

            $this->stdout("✓ Atlas generated: {$atlasFile}\n");
        }

        $this->stdout("\n✓ All atlases generated successfully!\n");
        return 0;
    }

    /**
     * Generate 16 variants from a source sprite
     */
    private function generateVariantsForState($sourceFile)
    {
        $baseImage = imagecreatefrompng($sourceFile);
        if (!$baseImage) {
            throw new \Exception("Failed to load: {$sourceFile}");
        }

        $width = imagesx($baseImage);
        $height = imagesy($baseImage);

        // Extract triangular sections
        $triangles = [
            'up' => $this->extractTriangle($baseImage, $width, $height, 'up'),
            'down' => $this->extractTriangle($baseImage, $width, $height, 'down'),
            'left' => $this->extractTriangle($baseImage, $width, $height, 'left'),
            'right' => $this->extractTriangle($baseImage, $width, $height, 'right'),
        ];

        // Generate all 16 variants
        $variants = [];
        for ($variant = 0; $variant < 16; $variant++) {
            $variants[$variant] = $this->generateVariant($triangles, $variant, $width, $height);
        }

        // Cleanup
        foreach ($triangles as $triangle) {
            imagedestroy($triangle);
        }
        imagedestroy($baseImage);

        return $variants;
    }

    /**
     * Create atlas from variant images
     */
    private function createAtlasFromVariants($variants, $basePath, $state)
    {
        $atlasWidth = 1024; // 16 * 64
        $atlasHeight = 64;

        $atlas = imagecreatetruecolor($atlasWidth, $atlasHeight);
        imagealphablending($atlas, false);
        $transparent = imagecolorallocatealpha($atlas, 0, 0, 0, 127);
        imagefill($atlas, 0, 0, $transparent);
        imagesavealpha($atlas, true);

        // Copy each variant to atlas
        for ($i = 0; $i < 16; $i++) {
            if (!isset($variants[$i])) continue;

            $xOffset = $i * 64;
            imagecopy($atlas, $variants[$i], $xOffset, 0, 0, 0, 64, 64);
        }

        // Save atlas
        $atlasFile = $basePath . '/pipe_atlas_' . $state . '.png';
        imagepng($atlas, $atlasFile);
        imagedestroy($atlas);

        return $atlasFile;
    }

    /**
     * Generate construction sprites (10%-90% progress)
     * Shows building progress with opacity
     *
     * Usage: php yii pipe/generate-construction
     */
    public function actionGenerateConstruction()
    {
        $basePath = dirname(__DIR__, 2) . '/public/assets/tiles/entities/pipe';
        $sourceFile = $basePath . '/normal.png';

        if (!file_exists($sourceFile)) {
            $this->stderr("Source file not found: {$sourceFile}\n");
            return 1;
        }

        $baseImage = imagecreatefrompng($sourceFile);
        if (!$baseImage) {
            $this->stderr("Failed to load base image\n");
            return 1;
        }

        $width = imagesx($baseImage);
        $height = imagesy($baseImage);

        // Generate construction sprites for 10%-90%
        for ($progress = 10; $progress <= 90; $progress += 10) {
            $constructionImage = imagecreatetruecolor($width, $height);
            imagealphablending($constructionImage, false);
            $transparent = imagecolorallocatealpha($constructionImage, 0, 0, 0, 127);
            imagefill($constructionImage, 0, 0, $transparent);
            imagesavealpha($constructionImage, true);

            // Calculate opacity (10% progress = 10% opacity, 90% progress = 90% opacity)
            $opacity = $progress / 100.0;

            // Copy pixels with adjusted alpha
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $color = imagecolorat($baseImage, $x, $y);
                    $alpha = ($color >> 24) & 0xFF;

                    // Skip fully transparent pixels
                    if ($alpha == 127) continue;

                    // Calculate new alpha based on progress
                    $newAlpha = 127 - ((127 - $alpha) * $opacity);
                    $newAlpha = max(0, min(127, (int)$newAlpha));

                    $r = ($color >> 16) & 0xFF;
                    $g = ($color >> 8) & 0xFF;
                    $b = $color & 0xFF;

                    $newColor = imagecolorallocatealpha($constructionImage, $r, $g, $b, $newAlpha);
                    imagesetpixel($constructionImage, $x, $y, $newColor);
                }
            }

            // Save construction sprite
            $outputFile = $basePath . "/construction_{$progress}.png";
            imagepng($constructionImage, $outputFile);
            imagedestroy($constructionImage);

            $this->stdout("✓ Generated construction_{$progress}.png (opacity: {$progress}%)\n");
        }

        imagedestroy($baseImage);

        $this->stdout("\n✓ All construction sprites generated!\n");
        return 0;
    }

    /**
     * Extract triangular section from sprite
     * @param resource $image - Source image
     * @param int $width - Image width
     * @param int $height - Image height
     * @param string $direction - 'up', 'down', 'left', 'right'
     * @return resource - New image with only triangle pixels
     */
    private function extractTriangle($image, $width, $height, $direction)
    {
        // Create transparent canvas
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);
        imagesavealpha($result, true);

        // Copy pixels that belong to triangle
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                if ($this->isInTriangle($x, $y, $width, $height, $direction)) {
                    $color = imagecolorat($image, $x, $y);
                    imagesetpixel($result, $x, $y, $color);
                }
            }
        }

        return $result;
    }

    /**
     * Check if point (x,y) is inside specified triangle
     * Triangles are divided by two diagonals from center:
     * - Diagonal 1: (0,0) to (width,height) - equation: y = x
     * - Diagonal 2: (0,height) to (width,0) - equation: y = height - x
     */
    private function isInTriangle($x, $y, $width, $height, $direction)
    {
        // Diagonal 1: y = x (main diagonal)
        // Diagonal 2: y = height - x (anti-diagonal)

        switch ($direction) {
            case 'up':
                // Above both diagonals: y <= x AND y <= (height - x)
                return $y <= $x && $y <= ($height - $x);

            case 'down':
                // Below both diagonals: y >= x AND y >= (height - x)
                return $y >= $x && $y >= ($height - $x);

            case 'left':
                // Left of both diagonals: y >= x AND y <= (height - x)
                return $y >= $x && $y <= ($height - $x);

            case 'right':
                // Right of both diagonals: y <= x AND y >= (height - x)
                return $y <= $x && $y >= ($height - $x);

            default:
                return false;
        }
    }

    /**
     * Generate variant by composing rotated triangles
     * Base sprite is horizontal pipe (left-right)
     * - triangles['left'] = left half with pipe
     * - triangles['right'] = right half with pipe
     * - triangles['up'] = empty space above pipe
     * - triangles['down'] = empty space below pipe
     */
    private function generateVariant($triangles, $variant, $width, $height)
    {
        // Create result image
        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, true); // Enable blending for overlaying
        $transparent = imagecolorallocatealpha($result, 0, 0, 0, 127);
        imagefill($result, 0, 0, $transparent);
        imagesavealpha($result, true);

        // Decode binary flags: right(1), down(2), left(4), up(8)
        $hasRight = ($variant & 1) > 0;
        $hasDown = ($variant & 2) > 0;
        $hasLeft = ($variant & 4) > 0;
        $hasUp = ($variant & 8) > 0;

        // Compose triangles for each direction
        // RIGHT: use right triangle as-is (0°)
        if ($hasRight) {
            imagecopy($result, $triangles['right'], 0, 0, 0, 0, $width, $height);
        }

        // LEFT: use left triangle as-is (0°)
        if ($hasLeft) {
            imagecopy($result, $triangles['left'], 0, 0, 0, 0, $width, $height);
        }

        // UP: rotate right triangle by -90° (counter-clockwise)
        if ($hasUp) {
            $rotated = imagerotate($triangles['right'], 90, $transparent); // GD rotates opposite
            imagealphablending($rotated, false);
            imagesavealpha($rotated, true);
            imagecopy($result, $rotated, 0, 0, 0, 0, $width, $height);
            imagedestroy($rotated);
        }

        // DOWN: rotate right triangle by 90° (clockwise)
        if ($hasDown) {
            $rotated = imagerotate($triangles['right'], -90, $transparent); // GD rotates opposite
            imagealphablending($rotated, false);
            imagesavealpha($rotated, true);
            imagecopy($result, $rotated, 0, 0, 0, 0, $width, $height);
            imagedestroy($rotated);
        }

        return $result;
    }

}
