<?php

namespace bl\entity\sprite_generators\eye;

use bl\entity\sprite_generators\base\AbstractSpriteGenerator;

/**
 * Base class for eye (crystal tower) generators
 */
abstract class AbstractEyeGenerator extends AbstractSpriteGenerator
{
    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality';
    }
}
