<?php

namespace generators;

use generators\base\BaseSpriteGenerator;
use models\EntityType;

/**
 * Генератор спрайтов для ресурсов (руды)
 */
class ResourceGenerator extends BaseSpriteGenerator
{
    public function getPrompts(): array
    {
        return [
            'ore_iron' => [
                'positive' => 'iron ore deposit, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, metallic gray rocks, iron ore stones, detailed mineral surface, resource deposit, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, refined metal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'ore_copper' => [
                'positive' => 'copper ore deposit, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, orange-brown copper rocks, detailed mineral surface, resource deposit, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, refined metal, multiple objects, landscape, ground, blurry, low quality'
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
}
