<?php

namespace bl\landing\generators\sky;

/**
 * Generator for sky landing sprites
 * Sky is the background below the floating island - not generated via AI
 */
class SkyLandingGenerator extends AbstractSkyLandingGenerator
{
    public function getFolder(): string
    {
        return 'sky';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, blue sky with white clouds, bright day sky, top-down view, game texture';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, ground, buildings, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'few clouds',
            'more clouds',
            'lighter blue',
            'darker blue',
        ];
    }

    /**
     * @inheritDoc
     * Sky uses seamless tiling
     */
    public function shouldMakeSeamless(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * Sky is not transparent
     */
    public function shouldMakeBottomTransparent(): bool
    {
        return false;
    }
}
