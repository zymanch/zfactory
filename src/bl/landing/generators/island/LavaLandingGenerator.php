<?php

namespace bl\landing\generators\island;

/**
 * Generator for lava landing sprites
 */
class LavaLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'lava';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, lava surface, molten rock, orange red glow, cracks, top-down view, game texture, dramatic';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, water, ice, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'more cracks',
            'brighter glow',
            'darker cooled areas',
            'flowing',
        ];
    }
}
