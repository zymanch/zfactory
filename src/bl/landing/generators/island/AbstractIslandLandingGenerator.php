<?php

namespace bl\landing\generators\island;

use bl\landing\generators\base\AbstractLandingGenerator;

/**
 * Abstract base class for island terrain landing generators
 * Island landings are buildable terrains on the floating island (grass, dirt, sand, etc.)
 */
abstract class AbstractIslandLandingGenerator extends AbstractLandingGenerator
{
    /**
     * @inheritDoc
     */
    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, text, watermark, 3d perspective, side view';
    }

    /**
     * @inheritDoc
     * Island landings always use seamless tiling
     */
    public function shouldMakeSeamless(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * Island landings are never transparent
     */
    public function shouldMakeBottomTransparent(): bool
    {
        return false;
    }
}
