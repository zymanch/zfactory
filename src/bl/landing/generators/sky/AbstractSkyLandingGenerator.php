<?php

namespace bl\landing\generators\sky;

use bl\landing\generators\base\AbstractLandingGenerator;

/**
 * Abstract base class for sky-related landing generators
 * Sky landings are below the floating island (sky background, island edge/stalactites)
 */
abstract class AbstractSkyLandingGenerator extends AbstractLandingGenerator
{
    /**
     * @inheritDoc
     */
    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, text, watermark, top-down view, aerial view, birds eye';
    }
}
