<?php

namespace bl\landing\generators\island;

/**
 * Generator for grass landing sprites
 */
class GrassLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'grass';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, grass field texture from directly above, overhead shot, satellite view, flat surface, no perspective, short grass, natural green tones, varied grass pattern, game texture, top-down orthographic, photorealistic, high detail';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'solid color, flat color, uniform, neon, oversaturated, side view, perspective, 3d, depth, horizon line, camera angle, tall grass, blurry, low quality, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'small flowers',
            'darker shade',
            'lighter shade',
            'tiny patches',
        ];
    }
}
