<?php

namespace bl\landing\generators\island;

/**
 * Generator for water landing sprites
 */
class WaterLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'water';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, water surface, blue water, ripples, top-down view, game texture, clear';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, land, rocks, text, watermark, foam';
    }

    public function getVariationPrompts(): array
    {
        return [
            'calm',
            'ripples',
            'darker blue',
            'lighter blue',
        ];
    }
}
