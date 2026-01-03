<?php

namespace bl\landing\generators\island;

/**
 * Generator for stone landing sprites
 */
class StoneLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'stone';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, stone surface, grey rocks, top-down view, game texture, natural pattern';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, grass, dirt, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'mossy patches',
            'darker grey',
            'lighter grey',
            'rough texture',
        ];
    }
}
