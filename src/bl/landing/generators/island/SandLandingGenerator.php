<?php

namespace bl\landing\generators\island;

/**
 * Generator for sand landing sprites
 */
class SandLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'sand';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, sand beach, golden sand, top-down view, game texture, clean, fine grain';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, water, rocks, grass, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'fine grain',
            'coarse grain',
            'golden tint',
            'white sand',
        ];
    }
}
