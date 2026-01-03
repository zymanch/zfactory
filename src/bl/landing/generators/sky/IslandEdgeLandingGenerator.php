<?php

namespace bl\landing\generators\sky;

/**
 * Generator for island edge landing sprites (stalactites)
 * Island edge shows the bottom of the floating island with hanging stalactites
 */
class IslandEdgeLandingGenerator extends AbstractSkyLandingGenerator
{
    public function getFolder(): string
    {
        return 'island_edge';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, rocky cliff edge, stalactites hanging down, stone formations, bottom edge, game texture, dramatic';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, grass, sky, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'longer stalactites',
            'shorter formations',
            'rougher texture',
            'smoother edge',
        ];
    }

    /**
     * @inheritDoc
     * Island edge uses seamless tiling for horizontal edges
     */
    public function shouldMakeSeamless(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * Island edge has transparent bottom (stalactites fading into sky)
     */
    public function shouldMakeBottomTransparent(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * 50% of bottom is transparent
     */
    public function getBottomTransparencyHeight(): float
    {
        return 0.5;
    }
}
