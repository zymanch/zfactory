<?php

namespace bl\entity\sprite_generators\manipulator;

use bl\entity\sprite_generators\base\AbstractSpriteGenerator;

/**
 * Base class for manipulator generators
 * Manipulators are rotational entities
 */
abstract class AbstractManipulatorGenerator extends AbstractSpriteGenerator
{
    public function isRotational(): bool
    {
        return true;
    }

    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, multiple objects, landscape, ground, blurry, low quality';
    }
}
