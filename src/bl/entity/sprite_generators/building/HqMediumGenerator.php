<?php

namespace bl\entity\sprite_generators\building;

class HqMediumGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'hq_medium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'medium-sized square industrial building, factory headquarters, top-down bird eye view, moderate complexity machinery, several chimneys, metal framework, steam pipes visible, mechanical parts, 4x4 grid size, industrial textures, detailed rooftop equipment, factory complex from above, square shape, perfectly aligned, game sprite, 2D game asset, clean white background, photorealistic industrial rendering, realistic metal and steel, straight orthogonal view, no perspective, flat top-down angle, professional game art, no shadows';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'circle, round, oval, isometric diamond base, platform, tilted angle, 45 degree view, perspective distortion, diagonal, simple building, plain structure, cartoon, anime, stylized, flat shading, multiple objects, landscape, ground, blurry, low quality, overly simplistic, too small';
    }
}
