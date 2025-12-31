<?php

namespace bl\entity\generators\base;

use app\client\ComfyUIClient;
use models\EntityType;
use Yii;

/**
 * Abstract base class for entity sprite generators
 * Each entity type should have its own generator class
 */
abstract class AbstractEntityGenerator
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
     * Get the entity image_url (folder name) this generator handles
     * @return string
     */
    abstract public function getImageUrl(): string;

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
     * Get generation scale multiplier (default: 4x)
     * Higher values = more detail but slower generation
     * @return int
     */
    public function getFluxGenerationScale(): int
    {
        return 4;
    }

    /**
     * Whether to generate state variants (damaged, blueprint, selected)
     * Override to false for deposits, trees, etc.
     * @return bool
     */
    public function shouldGenerateStates(): bool
    {
        return true;
    }

    /**
     * Whether to remove white background after generation
     * @return bool
     */
    public function shouldRemoveBackground(): bool
    {
        return true;
    }

    /**
     * Whether this is a rotational entity (conveyor, manipulator)
     * If true, only base orientation is generated, others are rotated
     * @return bool
     */
    public function isRotational(): bool
    {
        return false;
    }

    /**
     * Get rotation angles for rotational variants
     * @return array ['_up' => 270, '_down' => 90, '_left' => 180]
     */
    public function getRotationAngles(): array
    {
        return [
            '_up' => 270,
            '_down' => 90,
            '_left' => 180,
        ];
    }

    /**
     * Background brightness threshold for removal (0-255)
     * @return int
     */
    public function getBackgroundThreshold(): int
    {
        return 200;
    }

    /**
     * Generate sprite for entity type
     * @param EntityType $entity
     * @param bool $testMode If true, only generate normal.png
     * @return bool Success
     */
    public function generate(EntityType $entity, bool $testMode = false): bool
    {
        $imageUrl = $entity->image_url;

        // Skip rotational variants
        if ($this->isRotational() && $this->isRotationalVariant($imageUrl)) {
            echo "  Skipping rotational variant: {$imageUrl} (will be rotated from base)\n";
            return true;
        }

        $pixelWidth = $entity->width * $this->getTileWidth();
        $pixelHeight = $entity->height * $this->getTileHeight();
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

        $entityDir = $this->getEntityDir($imageUrl);
        if (!is_dir($entityDir)) {
            mkdir($entityDir, 0755, true);
        }

        $normalPath = $entityDir . '/normal.png';
        $result->saveToFile($normalPath);

        // Post-processing
        if ($this->shouldRemoveBackground()) {
            ImageProcessor::removeBackground($normalPath, $this->getBackgroundThreshold());
        }
        ImageProcessor::scaleImage($normalPath, $pixelWidth, $pixelHeight);

        echo "  Generated normal.png\n";

        if ($testMode) {
            return true;
        }

        // Generate states
        if ($this->shouldGenerateStates()) {
            $this->generateStates($entity);
        }

        return true;
    }

    /**
     * Generate state variants (damaged, blueprint, selected)
     * @param EntityType $entity
     * @return bool
     */
    public function generateStates(EntityType $entity): bool
    {
        $imageUrl = $entity->image_url;
        $entityDir = $this->getEntityDir($imageUrl);
        $normalPath = $entityDir . '/normal.png';

        if (!file_exists($normalPath)) {
            echo "  Error: normal.png not found\n";
            return false;
        }

        echo "  Generating states...\n";

        // Damaged
        ImageProcessor::createDamaged($normalPath, $entityDir . '/damaged.png');
        echo "    Created damaged.png\n";

        // Blueprint
        ImageProcessor::createBlueprint($normalPath, $entityDir . '/blueprint.png', $imageUrl);
        echo "    Created blueprint.png\n";

        // Selected variants
        ImageProcessor::createSelected($normalPath, $entityDir . '/normal_selected.png');
        echo "    Created normal_selected.png\n";

        ImageProcessor::createSelected($entityDir . '/damaged.png', $entityDir . '/damaged_selected.png');
        echo "    Created damaged_selected.png\n";

        return true;
    }

    /**
     * Generate rotational variants from base sprite
     * @param EntityType $baseEntity
     * @return bool
     */
    public function generateRotationalVariants(EntityType $baseEntity): bool
    {
        if (!$this->isRotational()) {
            return true;
        }

        $baseImageUrl = $baseEntity->image_url;
        $baseDir = $this->getEntityDir($baseImageUrl);

        foreach ($this->getRotationAngles() as $suffix => $angle) {
            $variantUrl = $baseImageUrl . $suffix;

            // Check if variant exists in DB
            $variantEntity = EntityType::find()
                ->where(['image_url' => $variantUrl])
                ->one();

            if (!$variantEntity) {
                continue;
            }

            echo "  Creating {$variantUrl} from {$baseImageUrl} (rotate {$angle})...\n";

            $variantDir = $this->getEntityDir($variantUrl);
            if (!is_dir($variantDir)) {
                mkdir($variantDir, 0755, true);
            }

            // Rotate all sprite states
            $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
            foreach ($states as $state) {
                $srcPath = $baseDir . '/' . $state . '.png';
                $destPath = $variantDir . '/' . $state . '.png';

                if (file_exists($srcPath)) {
                    ImageProcessor::rotateImage($srcPath, $destPath, $angle);
                }
            }

            echo "    Created {$variantUrl}\n";
        }

        return true;
    }

    /**
     * Get entity sprites directory
     * @param string $imageUrl
     * @return string
     */
    protected function getEntityDir(string $imageUrl): string
    {
        return $this->basePath . '/public/assets/tiles/entities/' . $imageUrl;
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

    /**
     * Check if image_url is a rotational variant
     * @param string $imageUrl
     * @return bool
     */
    protected function isRotationalVariant(string $imageUrl): bool
    {
        $suffixes = array_keys($this->getRotationAngles());
        foreach ($suffixes as $suffix) {
            if (substr($imageUrl, -strlen($suffix)) === $suffix) {
                return true;
            }
        }
        return false;
    }
}
