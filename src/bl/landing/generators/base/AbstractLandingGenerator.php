<?php

namespace bl\landing\generators\base;

use app\client\ComfyUIClient;
use app\client\StableDiffusionClient;
use models\Landing;
use Yii;

/**
 * Abstract base class for landing sprite generators
 * Each landing type should have its own generator class
 */
abstract class AbstractLandingGenerator
{
    /** @var ComfyUIClient|null */
    protected $fluxClient;

    /** @var StableDiffusionClient|null */
    protected $sdClient;

    /** @var string */
    protected $basePath;

    public function __construct(
        ?ComfyUIClient $fluxClient = null,
        ?StableDiffusionClient $sdClient = null,
        ?string $basePath = null
    ) {
        $this->fluxClient = $fluxClient;
        $this->sdClient = $sdClient;
        $this->basePath = $basePath ?? Yii::getAlias('@app/..');
    }

    /**
     * Get the landing folder name this generator handles
     * @return string
     */
    abstract public function getFolder(): string;

    /**
     * Get positive prompt for FLUX AI generation
     * @return string
     */
    abstract public function getFluxPositivePrompt(): string;

    /**
     * Get negative prompt for FLUX AI generation
     * @return string
     */
    abstract public function getFluxNegativePrompt(): string;

    /**
     * Get positive prompt for Stable Diffusion generation
     * @return string
     */
    protected function getStableDiffusionPositivePrompt(): string
    {
        return $this->getFluxPositivePrompt();
    }

    /**
     * Get negative prompt for Stable Diffusion generation
     * @return string
     */
    protected function getStableDiffusionNegativePrompt(): string
    {
        return $this->getFluxNegativePrompt();
    }

    /**
     * Get variation prompts for img2img generation
     * @return array
     */
    abstract public function getVariationPrompts(): array;

    /**
     * Get number of variations to generate
     * @return int
     */
    public function getVariationsCount(): int
    {
        return 5;
    }

    /**
     * Get generation width for AI
     * @return int
     */
    protected function getFluxGenerationWidth(): int
    {
        return 512;
    }

    /**
     * Get generation height for AI
     * @return int
     */
    protected function getFluxGenerationHeight(): int
    {
        return 384;
    }

    /**
     * Get FLUX generation steps
     * @return int
     */
    protected function getFluxSteps(): int
    {
        return 28;
    }

    /**
     * Get FLUX CFG scale
     * @return float
     */
    protected function getFluxCfgScale(): float
    {
        return 1.5;
    }

    /**
     * Whether to apply seamless tiling post-processing
     * @return bool
     */
    protected function shouldMakeSeamless(): bool
    {
        return true;
    }

    /**
     * Whether to apply bottom transparency (for edge types)
     * @return bool
     */
    protected function shouldMakeBottomTransparent(): bool
    {
        return false;
    }

    /**
     * Get bottom transparency percentage (0.0-1.0)
     * @return float
     */
    protected function getBottomTransparencyHeight(): float
    {
        return 0.5;
    }

    /**
     * Generate landing sprites using FLUX
     * @param Landing $landing
     * @param bool $testMode If true, generate only base sprite
     * @return bool
     */
    public function generateWithFlux(Landing $landing, bool $testMode = false): bool
    {
        if (!$this->fluxClient) {
            echo "  Error: FLUX client not available\n";
            return false;
        }

        $landingDir = $this->getLandingDir();
        if (!is_dir($landingDir)) {
            mkdir($landingDir, 0755, true);
        }

        // Generate base sprite
        echo "  Generating base sprite with FLUX...\n";
        $result = $this->fluxClient->txt2img(
            $this->getFluxPositivePrompt(),
            $this->getFluxNegativePrompt(),
            $this->getFluxGenerationWidth(),
            $this->getFluxGenerationHeight(),
            ['steps' => $this->getFluxSteps(), 'cfg' => $this->getFluxCfgScale()]
        );

        if (!$result) {
            echo "  Error: Failed to generate base sprite\n";
            return false;
        }

        $originalPath = $this->getOriginalPath(0);
        $result->saveToFile($originalPath);

        // Post-processing
        if ($this->shouldMakeSeamless()) {
            LandingImageProcessor::makeSeamless($originalPath);
        }

        if ($this->shouldMakeBottomTransparent()) {
            LandingImageProcessor::makeBottomTransparent($originalPath, $this->getBottomTransparencyHeight());
        }

        echo "  Saved base sprite\n";

        // Generate variations (skip in test mode)
        if (!$testMode) {
            $this->generateFluxVariations();
        }

        return true;
    }

    /**
     * Generate variations using FLUX
     */
    protected function generateFluxVariations(): void
    {
        $variationPrompts = $this->getVariationPrompts();
        $variationsCount = min($this->getVariationsCount() - 1, count($variationPrompts));

        echo "  Generating {$variationsCount} variations...\n";

        for ($i = 0; $i < $variationsCount; $i++) {
            $varPrompt = $this->getFluxPositivePrompt();
            if (isset($variationPrompts[$i]) && !empty($variationPrompts[$i])) {
                $varPrompt .= ', ' . $variationPrompts[$i];
            }

            $result = $this->fluxClient->txt2img(
                $varPrompt,
                $this->getFluxNegativePrompt(),
                $this->getFluxGenerationWidth(),
                $this->getFluxGenerationHeight(),
                ['steps' => $this->getFluxSteps(), 'cfg' => $this->getFluxCfgScale()]
            );

            if (!$result) {
                echo "    Warning: Failed to generate variation " . ($i + 1) . "\n";
                continue;
            }

            $varPath = $this->getOriginalPath($i + 1);
            $result->saveToFile($varPath);

            if ($this->shouldMakeSeamless()) {
                LandingImageProcessor::makeSeamless($varPath);
            }

            if ($this->shouldMakeBottomTransparent()) {
                LandingImageProcessor::makeBottomTransparent($varPath, $this->getBottomTransparencyHeight());
            }

            echo "    Saved variation " . ($i + 1) . "\n";
        }
    }

    /**
     * Load sprite variation as GD resource
     * @param int $variation Variation number (0-4)
     * @return resource GD image resource
     */
    protected function loadSpriteVariation(int $variation)
    {
        $path = $this->getOriginalPath($variation);
        if (!file_exists($path)) {
            throw new \RuntimeException("Sprite variation {$variation} not found at {$path}");
        }

        $gd = imagecreatefrompng($path);
        if (!$gd) {
            throw new \RuntimeException("Failed to load sprite variation {$variation}");
        }

        imagealphablending($gd, false);
        imagesavealpha($gd, true);

        return $gd;
    }

    /**
     * Save GD resource as sprite variation
     * @param resource $gd GD image resource
     * @param int $variation Variation number (0-4)
     * @return bool Success
     */
    protected function saveSpriteVariation($gd, int $variation): bool
    {
        $path = $this->getOriginalPath($variation);
        $result = imagepng($gd, $path, 9);

        if ($result && $this->shouldMakeBottomTransparent()) {
            LandingImageProcessor::makeBottomTransparent($path, $this->getBottomTransparencyHeight());
        }

        return $result;
    }

    /**
     * Generate single sprite variation using Stable Diffusion img2img
     * @param int $variation Variation number (1-4, 0 is base sprite)
     * @return resource|false GD image resource or false on failure
     */
    public function generateSpriteVariation(int $variation)
    {
        if (!$this->sdClient) {
            echo "  Error: Stable Diffusion client not available\n";
            return false;
        }

        $originalPath = $this->getOriginalPath(0);
        if (!file_exists($originalPath)) {
            echo "  Error: Base image not found\n";
            return false;
        }

        $baseImageBase64 = base64_encode(file_get_contents($originalPath));
        $variationPrompts = $this->getVariationPrompts();

        // Build prompt for this variation
        $modifier = $variationPrompts[$variation - 1] ?? '';
        $varPrompt = $this->getStableDiffusionPositivePrompt();
        if (!empty($modifier)) {
            $varPrompt .= ', ' . $modifier;
        }

        // Generate with Stable Diffusion
        $result = $this->sdClient->img2img(
            $baseImageBase64,
            $varPrompt,
            $this->getStableDiffusionNegativePrompt(),
            $this->getFluxGenerationWidth(),
            $this->getFluxGenerationHeight(),
            ['denoising_strength' => 0.25]
        );

        if (!$result) {
            return false;
        }

        // Convert base64 to GD resource
        $imageData = base64_decode($result->imageBase64);
        $gd = imagecreatefromstring($imageData);

        if (!$gd) {
            return false;
        }

        imagealphablending($gd, false);
        imagesavealpha($gd, true);

        return $gd;
    }

    /**
     * Generate variations using Stable Diffusion img2img
     * @param Landing $landing
     * @return bool
     */
    public function generateVariationsWithStableDiffusion(Landing $landing): bool
    {
        if (!$this->sdClient) {
            echo "  Error: Stable Diffusion client not available\n";
            return false;
        }

        $originalPath = $this->getOriginalPath(0);
        if (!file_exists($originalPath)) {
            echo "  Error: Base image not found\n";
            return false;
        }

        $variationPrompts = $this->getVariationPrompts();
        $variationsCount = min($this->getVariationsCount() - 1, count($variationPrompts));

        echo "  Generating {$variationsCount} variations with Stable Diffusion...\n";

        for ($i = 0; $i < $variationsCount; $i++) {
            $variationNumber = $i + 1;
            $gd = $this->generateSpriteVariation($variationNumber);

            if (!$gd) {
                echo "    Warning: Failed to generate variation {$variationNumber}\n";
                continue;
            }

            $success = $this->saveSpriteVariation($gd, $variationNumber);
            imagedestroy($gd);

            if ($success) {
                echo "    Saved variation {$variationNumber}\n";
            } else {
                echo "    Warning: Failed to save variation {$variationNumber}\n";
            }
        }

        return true;
    }

    /**
     * Scale all original images to tile size
     * @param Landing $landing
     * @return bool
     */
    public function scaleOriginals(Landing $landing): bool
    {
        $tileWidth = $this->getTileWidth();
        $tileHeight = $this->getTileHeight();
        $variationsCount = $this->getVariationsCount();
        $scaledCount = 0;

        echo "  Scaling {$variationsCount} variations to {$tileWidth}x{$tileHeight}...\n";

        for ($i = 0; $i < $variationsCount; $i++) {
            $originalPath = $this->getOriginalPath($i);

            if (!file_exists($originalPath)) {
                // Fallback to base original
                $originalPath = $this->getOriginalPath(0);
            }

            if (!file_exists($originalPath)) {
                continue;
            }

            $scaledPath = $this->getScaledPath($i);
            LandingImageProcessor::scaleImage($originalPath, $scaledPath, $tileWidth, $tileHeight);
            $scaledCount++;
        }

        echo "    Scaled {$scaledCount} variations\n";
        return $scaledCount > 0;
    }

    /**
     * Get landing sprites directory
     * @return string
     */
    protected function getLandingDir(): string
    {
        return $this->basePath . '/public/assets/tiles/landing/' . $this->getFolder();
    }

    /**
     * Get path to original image file
     * @param int $variation
     * @return string
     */
    protected function getOriginalPath(int $variation): string
    {
        return $this->getLandingDir() . '/' . $this->getFolder() . '_' . $variation . '_original.png';
    }

    /**
     * Get path to scaled sprite file
     * @param int $variation
     * @return string
     */
    protected function getScaledPath(int $variation): string
    {
        return $this->getLandingDir() . '/' . $this->getFolder() . '_' . $variation . '.png';
    }

    /**
     * Get tile width from params
     * @return int
     */
    protected function getTileWidth(): int
    {
        return Yii::$app->params['tile_width'] ?? 64;
    }

    /**
     * Get tile height from params
     * @return int
     */
    protected function getTileHeight(): int
    {
        return Yii::$app->params['tile_height'] ?? 48;
    }
}
