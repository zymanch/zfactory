<?php

namespace generators;

use generators\base\BaseSpriteGenerator;
use models\EntityType;

/**
 * Генератор спрайтов для зданий (furnace, assembler, drill и т.д.)
 */
class BuildingGenerator extends BaseSpriteGenerator
{
    public function getPrompts(): array
    {
        return [
            'furnace' => [
                'positive' => 'stone furnace, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic stone texture, glowing fire inside, detailed metallic parts, smelting equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'assembler' => [
                'positive' => 'assembly machine, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, complex machinery, detailed mechanical parts, crafting machine, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'chest' => [
                'positive' => 'wooden storage chest, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, detailed wood grain, metal fittings, storage container, inventory box, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'power_pole' => [
                'positive' => 'wooden power pole, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, detailed wood grain, electricity pole with insulators, power tower, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality'
            ],
            'steam_engine' => [
                'positive' => 'steam engine turbine, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, power generator, factory building, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'boiler' => [
                'positive' => 'water boiler, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed rivets and pipes, steam generator, factory equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'drill' => [
                'positive' => 'mining drill, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, ore extractor, mining equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality'
            ],
            'drill_fast' => [
                'positive' => 'advanced mining drill, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, faster ore extractor, advanced technology, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality'
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
