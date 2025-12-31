<?php

namespace bl\entity\generators\manipulator;

use bl\entity\generators\base\AbstractEntityGenerator;

/**
 * Base class for manipulator generators
 * Manipulators are rotational entities
 */
abstract class AbstractManipulatorGenerator extends AbstractEntityGenerator
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
