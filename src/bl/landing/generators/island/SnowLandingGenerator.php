<?php

namespace bl\landing\generators\island;

/**
 * Generator for snow landing sprites
 */
class SnowLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'snow';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, detailed snow surface, snow crystals, subtle shadows and highlights, varied white and light blue tones, natural snow texture, icy patches, top-down orthographic view, game asset, photorealistic, high detail';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'solid white, pure white, flat color, monochrome, uniform, blurry, low quality, dirt, grass, text, watermark, 3d perspective';
    }

    public function getVariationPrompts(): array
    {
        return [
            'fresh powder',
            'icy patches',
            'slight footprints',
            'pristine',
        ];
    }
}
