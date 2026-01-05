<?php

namespace bl\entity\generators\transporter;

use bl\entity\generators\base\AbstractEntityGenerator;
use bl\entity\generators\base\ImageProcessor;
use models\EntityType;

class ConveyorGenerator extends AbstractEntityGenerator
{
    public function getImageUrl(): string
    {
        return 'conveyor';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'flat conveyor belt, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal texture, gray metallic belt, detailed mechanical parts, no legs, no stand, no support structure, PERFECTLY HORIZONTAL ORIENTATION, straight from left to right, NOT diagonal, NOT angled, belt running horizontally across image, flat on surface, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, legs, stand, support structure, elevated, platform, base, diagonal angle, angled view, tilted, rotated, multiple objects, landscape, ground, blurry, low quality';
    }

    public function isRotational(): bool
    {
        return true;
    }

    /**
     * Generate conveyor sprite with special logic:
     * 1. If parent_entity_type_id exists - rotate from parent
     * 2. If entity_type_id=100 - generate with AI + apply mirror
     * 3. Otherwise - copy from entity_type_id=100 + apply hue shift
     *
     * @param EntityType $entity
     * @param bool $testMode
     * @return resource|false GD image resource or false on failure
     */
    public function generate(EntityType $entity, bool $testMode = false)
    {
        // Case 1: Rotational variant - rotate from parent
        if ($entity->parent_entity_type_id) {
            return $this->generateFromParent($entity);
        }

        // Case 2: Base conveyor (entity_type_id=100) - generate with AI
        if ($entity->entity_type_id == 100) {
            return $this->generateBaseConveyor($entity);
        }

        // Case 3: Color variant (dual, fast_dual) - copy from base + hue shift
        return $this->generateColorVariant($entity);
    }

    /**
     * Generate rotational variant from parent
     * @param EntityType $entity
     * @return resource|false
     */
    private function generateFromParent(EntityType $entity)
    {
        $parent = EntityType::findOne($entity->parent_entity_type_id);
        if (!$parent) {
            echo "  Error: Parent entity_type_id {$entity->parent_entity_type_id} not found\n";
            return false;
        }

        $gdImage = $parent->getNormalSprite();
        if (!$gdImage) {
            echo "  Error: Parent sprite not found\n";
            return false;
        }

        // Determine rotation angle from image_url suffix
        $angle = $this->getRotationAngleFromImageUrl($entity->image_url);

        // Rotate
        $transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127);
        $rotated = imagerotate($gdImage, -$angle, $transparent);
        imagealphablending($rotated, false);
        imagesavealpha($rotated, true);

        imagedestroy($gdImage);

        echo "  Generated {$entity->image_url} from parent (rotated {$angle}°)\n";
        return $rotated;
    }

    /**
     * Generate base conveyor with AI + mirror
     * @param EntityType $entity
     * @return resource|false
     */
    private function generateBaseConveyor(EntityType $entity)
    {
        $pixelWidth = $entity->width * $this->getTileWidth();
        $pixelHeight = $entity->height * $this->getTileHeight();
        $scale = $this->getFluxGenerationScale();
        $genWidth = $pixelWidth * $scale;
        $genHeight = $pixelHeight * $scale;

        echo "  Generating {$entity->image_url} with AI ({$pixelWidth}x{$pixelHeight}px, gen: {$genWidth}x{$genHeight}px)...\n";

        $result = $this->fluxClient->txt2img(
            $this->getFluxPositivePrompt(),
            $this->getFluxNegativePrompt(),
            $genWidth,
            $genHeight
        );

        if ($result === null) {
            echo "  Failed to generate image\n";
            return false;
        }

        // Convert ImageResult to GD resource
        $gdImage = imagecreatefromstring($result->getImageData());
        if (!$gdImage) {
            echo "  Failed to create GD resource\n";
            return false;
        }

        imagealphablending($gdImage, false);
        imagesavealpha($gdImage, true);

        // Apply post-processing
        if ($this->shouldRemoveBackground()) {
            $gdImage = $this->removeBackgroundGD($gdImage, $this->getBackgroundThreshold());
        }
        $gdImage = $this->scaleImageGD($gdImage, $pixelWidth, $pixelHeight);

        // Apply horizontal mirror (copy bottom half to top)
        $gdImage = $this->applyMirror($gdImage);

        echo "  Generated base conveyor with AI + mirror\n";
        return $gdImage;
    }

    /**
     * Generate color variant from base conveyor
     * @param EntityType $entity
     * @return resource|false
     */
    private function generateColorVariant(EntityType $entity)
    {
        // Load base conveyor (entity_type_id=100)
        $baseEntity = EntityType::findOne(100);
        if (!$baseEntity) {
            echo "  Error: Base conveyor (entity_type_id=100) not found\n";
            return false;
        }

        $gdImage = $baseEntity->getNormalSprite();
        if (!$gdImage) {
            echo "  Error: Base conveyor sprite not found\n";
            return false;
        }

        // Determine hue shift by entity type
        $hueShift = $this->getHueShiftForEntityType($entity->entity_type_id);

        // Apply hue shift
        $gdImage = $this->applyHueShift($gdImage, $hueShift);

        echo "  Generated {$entity->image_url} from base conveyor (hue shift: {$hueShift}°)\n";
        return $gdImage;
    }

    /**
     * Get rotation angle from image_url suffix
     * @param string $imageUrl
     * @return int
     */
    private function getRotationAngleFromImageUrl(string $imageUrl): int
    {
        if (strpos($imageUrl, '_up') !== false) return 270;
        if (strpos($imageUrl, '_down') !== false) return 90;
        if (strpos($imageUrl, '_left') !== false) return 180;
        return 0;
    }

    /**
     * Apply mirror effect (copy bottom half to top, flipped)
     * Based on ConveyorController::actionMirrorNormal
     * @param resource $gdImage
     * @return resource
     */
    private function applyMirror($gdImage)
    {
        $width = imagesx($gdImage);
        $height = imagesy($gdImage);
        $halfHeight = intval($height / 2);

        // Extract bottom half
        $bottomHalf = imagecreatetruecolor($width, $halfHeight);
        imagealphablending($bottomHalf, false);
        imagesavealpha($bottomHalf, true);
        imagecopy($bottomHalf, $gdImage, 0, 0, 0, $halfHeight, $width, $halfHeight);

        // Flip vertically
        imageflip($bottomHalf, IMG_FLIP_VERTICAL);

        // Copy to top half
        imagecopy($gdImage, $bottomHalf, 0, 0, 0, 0, $width, $halfHeight);

        imagedestroy($bottomHalf);
        return $gdImage;
    }

    /**
     * Get hue shift for entity type
     * @param int $entityTypeId
     * @return int Degrees (0-360)
     */
    private function getHueShiftForEntityType(int $entityTypeId): int
    {
        // Conveyor dual: 123, 124, 125, 126 (base + 3 orientations) - blue shift
        if ($entityTypeId >= 123 && $entityTypeId <= 126) {
            return 240; // Strong Blue
        }

        // Conveyor fast_dual: 127, 128, 129, 130 (base + 3 orientations) - green shift
        if ($entityTypeId >= 127 && $entityTypeId <= 130) {
            return 120; // Strong Green
        }

        return 0;
    }

    /**
     * Apply hue shift to image
     * @param resource $gdImage
     * @param int $hueShift Degrees (0-360)
     * @return resource
     */
    private function applyHueShift($gdImage, int $hueShift)
    {
        $width = imagesx($gdImage);
        $height = imagesy($gdImage);

        $result = imagecreatetruecolor($width, $height);
        imagealphablending($result, false);
        imagesavealpha($result, true);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($gdImage, $x, $y);
                $alpha = ($rgb >> 24) & 0x7F;
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Convert to HSL
                list($h, $s, $l) = $this->rgbToHsl($r, $g, $b);

                // Apply hue shift
                $h = fmod($h + $hueShift, 360);
                if ($h < 0) $h += 360;

                // Convert back to RGB
                list($r, $g, $b) = $this->hslToRgb($h, $s, $l);

                // Set pixel
                $newColor = imagecolorallocatealpha($result, (int)$r, (int)$g, (int)$b, $alpha);
                imagesetpixel($result, $x, $y, $newColor);
            }
        }

        imagedestroy($gdImage);
        return $result;
    }

    /**
     * Remove background from GD resource
     * @param resource $gdImage
     * @param int $threshold
     * @return resource
     */
    private function removeBackgroundGD($gdImage, int $threshold)
    {
        $width = imagesx($gdImage);
        $height = imagesy($gdImage);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($gdImage, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $brightness = ($r + $g + $b) / 3;

                if ($brightness > $threshold) {
                    $transparent = imagecolorallocatealpha($gdImage, 0, 0, 0, 127);
                    imagesetpixel($gdImage, $x, $y, $transparent);
                }
            }
        }

        return $gdImage;
    }

    /**
     * Scale GD image to target size
     * @param resource $gdImage
     * @param int $targetWidth
     * @param int $targetHeight
     * @return resource
     */
    private function scaleImageGD($gdImage, int $targetWidth, int $targetHeight)
    {
        $scaled = imagescale($gdImage, $targetWidth, $targetHeight, IMG_BICUBIC);
        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);

        imagedestroy($gdImage);
        return $scaled;
    }

    /**
     * Convert RGB to HSL
     * @param int $r 0-255
     * @param int $g 0-255
     * @param int $b 0-255
     * @return array [h, s, l] where h=0-360, s=0-1, l=0-1
     */
    private function rgbToHsl(int $r, int $g, int $b): array
    {
        $r /= 255;
        $g /= 255;
        $b /= 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max == $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            switch ($max) {
                case $r:
                    $h = (($g - $b) / $d + ($g < $b ? 6 : 0)) / 6;
                    break;
                case $g:
                    $h = (($b - $r) / $d + 2) / 6;
                    break;
                case $b:
                    $h = (($r - $g) / $d + 4) / 6;
                    break;
            }
        }

        return [$h * 360, $s, $l];
    }

    /**
     * Convert HSL to RGB
     * @param float $h 0-360
     * @param float $s 0-1
     * @param float $l 0-1
     * @return array [r, g, b] where each is 0-255
     */
    private function hslToRgb(float $h, float $s, float $l): array
    {
        $h /= 360;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hueToRgb($p, $q, $h + 1/3);
            $g = $this->hueToRgb($p, $q, $h);
            $b = $this->hueToRgb($p, $q, $h - 1/3);
        }

        return [round($r * 255), round($g * 255), round($b * 255)];
    }

    /**
     * Helper for HSL to RGB conversion
     * @param float $p
     * @param float $q
     * @param float $t
     * @return float
     */
    private function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }
}
