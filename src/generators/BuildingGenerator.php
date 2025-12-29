<?php

namespace generators;

use generators\base\BaseSpriteGenerator;
use models\EntityType;
use Yii;

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

            // Sawmills (построено на деревьях)
            'sawmill_small' => [
                'positive' => 'small wooden sawmill building, lumber mill, wood processing facility, rustic workshop, saws and logs visible, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, industrial building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'sawmill_medium' => [
                'positive' => 'medium sawmill complex, large lumber mill, multiple saws, log storage area, conveyor belts, industrial wood processing, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood and metal textures, factory building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'sawmill_large' => [
                'positive' => 'large industrial sawmill, massive lumber processing facility, automated machinery, log piles, cutting stations, steam-powered saws, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced factory, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Stone Quarries (построено на камнях)
            'stone_quarry_small' => [
                'positive' => 'small stone quarry, rock crushing machine, stone processing station, mining equipment, gravel pit, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic stone and metal textures, industrial facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'stone_quarry_medium' => [
                'positive' => 'medium stone quarry, crushing plant, conveyor belts for rocks, stone storage piles, industrial rock processing, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, mining complex, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'stone_quarry_large' => [
                'positive' => 'large industrial stone quarry, massive rock crusher, sorting facilities, storage silos, automated processing lines, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced mining facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Large Drill (железо/медь)
            'drill_large' => [
                'positive' => 'large mining drill, heavy industrial drilling rig, automated ore extraction, powerful machinery, metal framework, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic metal textures, advanced mining equipment, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Mines (серебро/золото)
            'mine_small' => [
                'positive' => 'small mine entrance, mineshaft with support beams, mining cart tracks, precious metal extraction, compact mining station, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood and stone textures, industrial building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'mine_medium' => [
                'positive' => 'medium mine complex, multiple shafts, ore processing area, mining carts, support structures, industrial mining facility, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, mining operation, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'mine_large' => [
                'positive' => 'large mining complex, deep shaft entrance, ore sorting facility, elevator system, industrial mine buildings, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced mining operation, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Quarries (алюминий/титан)
            'quarry_small' => [
                'positive' => 'small quarry for rare ores, aluminum titanium extraction, compact mining facility, modern equipment, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic metal and stone textures, industrial building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'quarry_medium' => [
                'positive' => 'medium quarry complex, rare metal extraction, aluminum and titanium processing, industrial facility, conveyor systems, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, mining facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],
            'quarry_large' => [
                'positive' => 'large industrial quarry, advanced rare metal extraction, aluminum titanium processing plant, automated systems, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows',
                'negative' => 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, platform, foundation, multiple objects, landscape, ground, blurry, low quality'
            ],

            // Headquarters (Main Building)
            'hq' => [
                'positive' => 'large square industrial building, complex factory headquarters, top-down bird eye view, intricate gears and cogs visible, steam pipes and chimneys, mechanical machinery parts, conveyor systems, metal framework, industrial textures, detailed rooftop equipment, factory complex from above, square shape, perfectly aligned, game sprite, 2D game asset, clean white background, photorealistic industrial rendering, highly detailed mechanical parts, realistic metal and steel, straight orthogonal view, no perspective, flat top-down angle, professional game art, no shadows',
                'negative' => 'circle, round, oval, isometric diamond base, platform, tilted angle, 45 degree view, perspective distortion, diagonal, simple building, plain structure, cartoon, anime, stylized, flat shading, multiple objects, landscape, ground, blurry, low quality'
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
        $pixelWidth = $entity->width * Yii::$app->params['tile_width'];
        $pixelHeight = $entity->height * Yii::$app->params['tile_height'];
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
