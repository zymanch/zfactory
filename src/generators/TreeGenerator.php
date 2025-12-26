<?php

namespace generators;

use generators\base\BaseSpriteGenerator;
use models\EntityType;
use Yii;
use yii\helpers\Console;

/**
 * Генератор спрайтов для деревьев
 */
class TreeGenerator extends BaseSpriteGenerator
{
    public function getPrompts(): array
    {
        return [
            'tree_pine' => [
                'positive' => 'pine tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, green pine needles, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_oak' => [
                'positive' => 'oak tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, green leafy canopy, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_dead' => [
                'positive' => 'dead tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, bare branches, gray trunk, withered, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, leaves, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_birch' => [
                'positive' => 'birch tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, white bark with black markings, green leafy canopy, slender trunk, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_willow' => [
                'positive' => 'willow tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, drooping branches, green hanging foliage, brown trunk, tall tree, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_maple' => [
                'positive' => 'maple tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, broad leafy canopy, distinctive maple leaves, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_spruce' => [
                'positive' => 'spruce tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, conical shape, dense green needles, brown trunk, tall evergreen, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
            'tree_ash' => [
                'positive' => 'ash tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, compound leaves, gray-brown bark, tall trunk, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality'
            ],
        ];
    }

    public function generate(EntityType $entity, bool $testMode = false): bool
    {
        $imageUrl = $entity->image_url;
        $prompts = $this->getPrompts();

        if (!isset($prompts[$imageUrl])) {
            echo "No prompt found for entity: {$imageUrl}\n";
            return false;
        }

        $prompt = $prompts[$imageUrl];
        $pixelWidth = $entity->width * self::TILE_WIDTH;
        $pixelHeight = $entity->height * self::TILE_HEIGHT;

        // Генерируем с увеличением 4x для предотвращения обрезки
        $genWidth = $pixelWidth * 4;
        $genHeight = $pixelHeight * 4;

        echo "  Generating {$imageUrl} ({$pixelWidth}x{$pixelHeight}px, gen: {$genWidth}x{$genHeight}px)...\n";

        // Генерируем через AI
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

        // Сохраняем
        $entityDir = $this->basePath . '/public/assets/tiles/entities/' . $imageUrl;
        if (!is_dir($entityDir)) {
            mkdir($entityDir, 0755, true);
        }

        $normalPath = $entityDir . '/normal.png';
        file_put_contents($normalPath, base64_decode($imageData));

        // Постобработка: удаление фона, масштабирование
        $this->removeBackground($normalPath);
        $this->scaleImage($normalPath, $pixelWidth, $pixelHeight);

        echo "  Generated normal.png\n";

        // В тестовом режиме - только normal.png
        if ($testMode) {
            return true;
        }

        // Генерируем состояния (damaged, blueprint, selected)
        return $this->generateStates($entity);
    }
}
