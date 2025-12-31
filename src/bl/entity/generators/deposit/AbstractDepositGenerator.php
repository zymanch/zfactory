<?php

namespace bl\entity\generators\deposit;

use app\client\ComfyUIClient;
use bl\entity\generators\base\ImageProcessor;
use models\DepositType;
use Yii;

/**
 * Base class for deposit generators (ores)
 * Deposits use DepositType model, not EntityType
 */
abstract class AbstractDepositGenerator
{
    /** @var ComfyUIClient */
    protected $fluxClient;

    /** @var string */
    protected $basePath;

    public function __construct(?ComfyUIClient $fluxClient = null, ?string $basePath = null)
    {
        $this->fluxClient = $fluxClient ?? new ComfyUIClient();
        $this->basePath = $basePath ?? Yii::getAlias('@app/..');
    }

    /**
     * Get the deposit image_url (folder name) this generator handles
     */
    abstract public function getImageUrl(): string;

    /**
     * Get positive prompt for FLUX AI generation
     */
    abstract public function getFluxPositivePrompt(): string;

    /**
     * Get negative prompt for FLUX AI generation
     */
    public function getFluxNegativePrompt(): string
    {
        return 'photorealistic, 3d model, blurry, background, landscape, refined metal, single rock, cropped edges, white background, empty space, text, watermark';
    }

    /**
     * Get generation scale multiplier
     */
    public function getFluxGenerationScale(): int
    {
        return 4;
    }

    /**
     * Background brightness threshold for removal
     */
    public function getBackgroundThreshold(): int
    {
        return 240;
    }

    /**
     * Generate sprite for deposit type
     */
    public function generate(DepositType $deposit): bool
    {
        $imageUrl = $deposit->image_url;

        $pixelWidth = $this->getTileWidth();
        $pixelHeight = $this->getTileHeight();
        $scale = $this->getFluxGenerationScale();
        $genWidth = $pixelWidth * $scale;
        $genHeight = $pixelHeight * $scale;

        echo "  Generating {$imageUrl} ({$pixelWidth}x{$pixelHeight}px, gen: {$genWidth}x{$genHeight}px)...\n";

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

        $depositDir = $this->getDepositDir($imageUrl);
        if (!is_dir($depositDir)) {
            mkdir($depositDir, 0755, true);
        }

        $normalPath = $depositDir . '/normal.png';
        $result->saveToFile($normalPath);

        // Post-processing
        ImageProcessor::removeBackground($normalPath, $this->getBackgroundThreshold());
        ImageProcessor::scaleImage($normalPath, $pixelWidth, $pixelHeight);

        echo "  ✓ Generated normal.png\n";

        return true;
    }

    /**
     * Get deposit sprites directory
     */
    protected function getDepositDir(string $imageUrl): string
    {
        return $this->basePath . '/public/assets/tiles/deposits/' . $imageUrl;
    }

    protected function getTileWidth(): int
    {
        return Yii::$app->params['tile_width'] ?? 64;
    }

    protected function getTileHeight(): int
    {
        return Yii::$app->params['tile_height'] ?? 48;
    }
}
