<?php

namespace generators;

use generators\base\BaseSpriteGenerator;
use models\EntityType;

/**
 * Генератор спрайтов для конвейеров
 * Генерирует только базовый конвейер (orientation=right), остальные создаются поворотом
 */
class ConveyorGenerator extends BaseSpriteGenerator
{
    public function getPrompts(): array
    {
        return [
            'conveyor' => [
                'positive' => 'flat conveyor belt, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal texture, gray metallic belt, detailed mechanical parts, no legs, no stand, no support structure, PERFECTLY HORIZONTAL ORIENTATION, straight from left to right, NOT diagonal, NOT angled, belt running horizontally across image, flat on surface, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, legs, stand, support structure, elevated, platform, base, diagonal angle, angled view, tilted, rotated, multiple objects, landscape, ground, blurry, low quality'
            ],
        ];
    }

    public function generate(EntityType $entity, bool $testMode = false): bool
    {
        $imageUrl = $entity->image_url;

        // Пропускаем ротационные варианты - они будут созданы поворотом
        if ($this->isRotationalVariant($imageUrl)) {
            echo "  Skipping rotational variant: {$imageUrl} (will be rotated from base)\n";
            return true;
        }

        $prompts = $this->getPrompts();

        if (!isset($prompts[$imageUrl])) {
            echo "No prompt found for entity: {$imageUrl}\n";
            return false;
        }

        $prompt = $prompts[$imageUrl];
        $pixelWidth = $entity->width * self::TILE_WIDTH;
        $pixelHeight = $entity->height * self::TILE_HEIGHT;
        $genWidth = $pixelWidth * 4;
        $genHeight = $pixelHeight * 4;

        echo "  Generating {$imageUrl} ({$pixelWidth}x{$pixelHeight}px, gen: {$genWidth}x{$genHeight}px)...\n";

        $imageData = $this->fluxAi->generate(
            $prompt['positive'],
            $prompt['negative'],
            $genWidth,
            $genHeight
        );

        if ($imageData === false) {
            echo "  Failed to generate image\n";
            return false;
        }

        $entityDir = $this->basePath . '/public/assets/tiles/entities/' . $imageUrl;
        if (!is_dir($entityDir)) {
            mkdir($entityDir, 0755, true);
        }

        $normalPath = $entityDir . '/normal.png';
        file_put_contents($normalPath, base64_decode($imageData));

        $this->removeBackground($normalPath);
        $this->scaleImage($normalPath, $pixelWidth, $pixelHeight);

        echo "  Generated normal.png\n";

        if ($testMode) {
            return true;
        }

        // Генерируем состояния (damaged, blueprint, selected)
        return $this->generateStates($entity);
    }

    /**
     * Генерирует ротационные варианты конвейеров (up, down, left) из базового (right)
     * @param array $entitiesToProcess Массив entity которые были обработаны
     */
    public function generateRotationalVariants($entitiesToProcess)
    {
        $rotationMap = [
            '_up' => 270,    // Rotate 270° CCW
            '_down' => 90,   // Rotate 90° CCW
            '_left' => 180,  // Rotate 180°
        ];

        $entityDir = $this->basePath . '/public/assets/tiles/entities';

        foreach ($entitiesToProcess as $name => $entity) {
            foreach ($rotationMap as $suffix => $angle) {
                $variantName = $name . $suffix;

                // Проверяем существует ли вариант в БД
                $variantEntity = \models\EntityType::find()
                    ->where(['image_url' => $variantName])
                    ->one();

                if (!$variantEntity) {
                    continue;
                }

                echo "  Creating {$variantName} from {$name} (rotate {$angle}°)...\n";

                $basePath = $entityDir . '/' . $name;
                $variantPath = $entityDir . '/' . $variantName;

                if (!is_dir($variantPath)) {
                    mkdir($variantPath, 0755, true);
                }

                // Rotate normal.png
                if (file_exists($basePath . '/normal.png')) {
                    $this->rotateImage($basePath . '/normal.png', $variantPath . '/normal.png', $angle);
                }

                // Rotate other states
                $states = ['damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
                foreach ($states as $state) {
                    if (file_exists($basePath . '/' . $state . '.png')) {
                        $this->rotateImage($basePath . '/' . $state . '.png', $variantPath . '/' . $state . '.png', $angle);
                    }
                }

                echo "  Created {$variantName}\n";
            }
        }
    }
}
