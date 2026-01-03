<?php

namespace bl\landing\generators\ship;

use bl\landing\generators\base\AbstractLandingGenerator;

/**
 * Abstract base class for ship landing generators
 * Ship landings are terrains on the spaceship (metal floors, edges)
 */
abstract class AbstractShipLandingGenerator extends AbstractLandingGenerator
{
    /**
     * @inheritDoc
     */
    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, text, watermark, side view, perspective, 3d';
    }

    /**
     * @inheritDoc
     * Ship landings always use seamless tiling
     */
    public function shouldMakeSeamless(): bool
    {
        return true;
    }
}
