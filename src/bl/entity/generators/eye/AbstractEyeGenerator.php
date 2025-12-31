<?php

namespace bl\entity\generators\eye;

use bl\entity\generators\base\AbstractEntityGenerator;

/**
 * Base class for eye (crystal tower) generators
 */
abstract class AbstractEyeGenerator extends AbstractEntityGenerator
{
    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, blurry, low quality';
    }
}
