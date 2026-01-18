<?php

namespace bl\entity\sprite_generators\transporter;

use bl\entity\sprite_generators\base\AbstractSpriteGenerator;
use bl\entity\sprite_generators\base\ImageProcessor;
use models\EntityType;

/**
 * Generator for pipe entities
 * Generates base horizontal pipe with AI, then rotates for vertical variant
 */
class PipeSpriteGenerator extends AbstractSpriteGenerator
{
    public function getImageUrl(): string
    {
        return 'pipe';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'industrial metal pipe, game sprite, top-down isometric view, single horizontal pipe, clean white background, photorealistic rendering, realistic metal texture, gray metallic pipe with visible welding seams, detailed metallic surface, weathered industrial look, transparent glass window in center showing pipe interior, 20 pixels height pipe, centered vertically, horizontal orientation left to right, game asset, professional quality, sharp details, no shadows, industrial machinery part';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, diagonal, vertical, angled, tilted, rotated, landscape, ground, blurry, low quality, colorful, bright colors, text, UI elements';
    }

    public function isRotational(): bool
    {
        // pipe_vertical is rotational variant
        return false; // We handle rotation manually
    }

    /**
     * Generate pipe sprite with special logic:
     * 1. entity_type_id=131 (pipe) - generate with AI
     * 2. entity_type_id=132 (pipe_vertical) - rotate from pipe
     * 3. For both: overlay transparent window in center (12x12px at 26-38, 26-38)
     *
     * @param EntityType $entity
     * @param bool $testMode
     * @return resource|false GD image resource or false on failure
     */
    public function generate(EntityType $entity, bool $testMode = false)
    {
        // Case 1: Vertical pipe - rotate from horizontal
        if ($entity->entity_type_id == 132 || $entity->image_url === 'pipe_vertical') {
            return $this->generateVerticalPipe($entity);
        }

        // Case 2: Base horizontal pipe (entity_type_id=131) - generate with AI
        if ($entity->entity_type_id == 131 || $entity->image_url === 'pipe') {
            return $this->generateBasePipe($entity, $testMode);
        }

        return false;
    }

    /**
     * Generate vertical pipe by rotating horizontal pipe 90°
     * @param EntityType $entity
     * @return resource|false
     */
    private function generateVerticalPipe(EntityType $entity)
    {
        // Load horizontal pipe sprite
        $horizontalPipe = EntityType::findOne(131);
        if (!$horizontalPipe) {
            echo "  Error: Horizontal pipe (entity_type_id=131) not found\n";
            return false;
        }

        $gdImage = $horizontalPipe->getNormalSprite();
        if (!$gdImage) {
            echo "  Error: Horizontal pipe sprite not found. Generate pipe (131) first.\n";
            return false;
        }

        // Rotate 90° clockwise
        $transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127);
        $rotated = imagerotate($gdImage, -90, $transparent);
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        imagedestroy($gdImage);

        echo "  Generated pipe_vertical from horizontal pipe (rotated 90°)\n";
        return $rotated;
    }

    /**
     * Generate base horizontal pipe with AI
     * Then overlay transparent window
     * @param EntityType $entity
     * @param bool $testMode
     * @return resource|false
     */
    private function generateBasePipe(EntityType $entity, bool $testMode)
    {
        // If normal.png already exists and we're in test mode, use existing
        $normalPath = $entity->getStatePath('normal');
        if ($testMode && file_exists($normalPath)) {
            echo "  Using existing pipe/normal.png\n";
            $gdImage = imagecreatefrompng($normalPath);
            if ($gdImage) {
                return $gdImage;
            }
        }

        // Generate with AI
        echo "  Generating horizontal pipe with AI (FLUX.1 Dev)...\n";

        $pixelWidth = $entity->width * $this->getTileWidth();
        $pixelHeight = $entity->height * $this->getTileHeight();
        $scale = $this->getFluxGenerationScale();

        $gdImage = $this->fluxClient->generateSprite(
            $this->getFluxPositivePrompt(),
            $this->getFluxNegativePrompt(),
            $pixelWidth * $scale,
            $pixelHeight * $scale
        );

        if (!$gdImage) {
            echo "  Error: Failed to generate sprite with AI\n";
            return false;
        }

        // Resize to target size
        $resized = ImageProcessor::resize($gdImage, $pixelWidth, $pixelHeight);
        imagedestroy($gdImage);

        if (!$resized) {
            echo "  Error: Failed to resize generated sprite\n";
            return false;
        }

        // Remove background
        if ($this->shouldRemoveBackground()) {
            $processed = ImageProcessor::removeBackground($resized);
            imagedestroy($resized);
            $resized = $processed;
        }

        // Overlay transparent window in center (12x12px at coordinates 26-38, 26-38)
        $this->overlayTransparentWindow($resized);

        echo "  Generated horizontal pipe with AI\n";
        return $resized;
    }

    /**
     * Overlay transparent window in center of pipe
     * Window: 10x10 pixels at coordinates (27, 27) to (36, 36)
     * @param resource $gdImage
     */
    private function overlayTransparentWindow($gdImage)
    {
        // Make sure alpha blending is off for transparency
        imagealphablending($gdImage, false);

        // Create fully transparent color
        $transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127);

        // Draw transparent rectangle (10x10 window)
        imagefilledrectangle($gdImage, 27, 27, 36, 36, $transparent);

        // Re-enable alpha blending
        imagealphablending($gdImage, true);
        imagesavealpha($gdImage, true);
    }

    /**
     * Get tile width from params
     */
    protected function getTileWidth(): int
    {
        return \Yii::$app->params['tile_width'] ?? 64;
    }

    /**
     * Get tile height from params
     */
    protected function getTileHeight(): int
    {
        return \Yii::$app->params['tile_height'] ?? 64;
    }
}
