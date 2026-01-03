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
    public function getStableDiffusionPositivePrompt(): string
    {
        return $this->getFluxPositivePrompt();
    }

    /**
     * Get negative prompt for Stable Diffusion generation
     * @return string
     */
    public function getStableDiffusionNegativePrompt(): string
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
    public function getFluxGenerationWidth(): int
    {
        return 512;
    }

    /**
     * Get generation height for AI
     * @return int
     */
    public function getFluxGenerationHeight(): int
    {
        return 384;
    }

    /**
     * Get FLUX generation steps
     * @return int
     */
    public function getFluxSteps(): int
    {
        return 28;
    }

    /**
     * Get FLUX CFG scale
     * @return float
     */
    public function getFluxCfgScale(): float
    {
        return 1.5;
    }

    /**
     * Whether to apply seamless tiling post-processing
     * @return bool
     */
    public function shouldMakeSeamless(): bool
    {
        return true;
    }

    /**
     * Whether to apply bottom transparency (for edge types)
     * @return bool
     */
    public function shouldMakeBottomTransparent(): bool
    {
        return false;
    }

    /**
     * Get bottom transparency percentage (0.0-1.0)
     * @return float
     */
    public function getBottomTransparencyHeight(): float
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

        $baseImageBase64 = base64_encode(file_get_contents($originalPath));
        $variationPrompts = $this->getVariationPrompts();
        $variationsCount = min($this->getVariationsCount() - 1, count($variationPrompts));

        echo "  Generating {$variationsCount} variations with Stable Diffusion...\n";

        for ($i = 0; $i < $variationsCount; $i++) {
            $modifier = $variationPrompts[$i] ?? '';
            $varPrompt = $this->getStableDiffusionPositivePrompt();
            if (!empty($modifier)) {
                $varPrompt .= ', ' . $modifier;
            }

            $result = $this->sdClient->img2img(
                $baseImageBase64,
                $varPrompt,
                $this->getStableDiffusionNegativePrompt(),
                $this->getFluxGenerationWidth(),
                $this->getFluxGenerationHeight(),
                ['denoising_strength' => 0.25]
            );

            if (!$result) {
                echo "    Warning: Failed to generate variation " . ($i + 1) . "\n";
                continue;
            }

            $varPath = $this->getOriginalPath($i + 1);
            file_put_contents($varPath, base64_decode($result->imageBase64));

            if ($this->shouldMakeBottomTransparent()) {
                LandingImageProcessor::makeBottomTransparent($varPath, $this->getBottomTransparencyHeight());
            }

            echo "    Saved variation " . ($i + 1) . "\n";
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
