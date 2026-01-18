<?php

namespace bl\entity\sprite_generators\building;

class HqSmallGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'hq_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'compact square industrial building, small factory headquarters, top-down bird eye view, simplified machinery, few chimneys, basic metal framework, small footprint, 3x3 grid size, industrial textures, basic rooftop equipment, compact factory from above, square shape, perfectly aligned, game sprite, 2D game asset, clean white background, photorealistic industrial rendering, detailed mechanical parts, realistic metal and steel, straight orthogonal view, no perspective, flat top-down angle, professional game art, no shadows';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'circle, round, oval, isometric diamond base, platform, tilted angle, 45 degree view, perspective distortion, diagonal, simple building, plain structure, cartoon, anime, stylized, flat shading, multiple objects, landscape, ground, blurry, low quality, overly complex, too large';
    }
}
