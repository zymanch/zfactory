<?php

namespace bl\entity\generators\relief;

use bl\entity\generators\base\AbstractEntityGenerator;

/**
 * Base class for relief generators (rocks)
 * Relief objects don't have states
 */
abstract class AbstractReliefGenerator extends AbstractEntityGenerator
{
    public function shouldGenerateStates(): bool
    {
        return false;
    }

    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, sky, blurry, low quality';
    }
}
