<?php

namespace bl\entity\sprite_generators\relief;

use bl\entity\sprite_generators\base\AbstractSpriteGenerator;

/**
 * Base class for relief generators (rocks)
 * Relief objects don't have states
 */
abstract class AbstractReliefGenerator extends AbstractSpriteGenerator
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
