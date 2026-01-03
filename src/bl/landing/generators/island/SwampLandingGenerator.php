<?php

namespace bl\landing\generators\island;

/**
 * Generator for swamp landing sprites
 */
class SwampLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'swamp';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, swamp ground, murky water, mud, dark green, top-down view, game texture';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, clean water, grass, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'more mud',
            'darker water',
            'algae',
            'murky',
        ];
    }
}
