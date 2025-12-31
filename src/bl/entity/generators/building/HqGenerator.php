<?php

namespace bl\entity\generators\building;

class HqGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'hq';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large square industrial building, complex factory headquarters, top-down bird eye view, intricate gears and cogs visible, steam pipes and chimneys, mechanical machinery parts, conveyor systems, metal framework, industrial textures, detailed rooftop equipment, factory complex from above, square shape, perfectly aligned, game sprite, 2D game asset, clean white background, photorealistic industrial rendering, highly detailed mechanical parts, realistic metal and steel, straight orthogonal view, no perspective, flat top-down angle, professional game art, no shadows';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'circle, round, oval, isometric diamond base, platform, tilted angle, 45 degree view, perspective distortion, diagonal, simple building, plain structure, cartoon, anime, stylized, flat shading, multiple objects, landscape, ground, blurry, low quality';
    }
}
