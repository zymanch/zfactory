<?php

namespace bl\entity\sprite_generators\tree;

use bl\entity\sprite_generators\base\AbstractSpriteGenerator;

/**
 * Base class for tree generators
 * Trees don't have states (damaged, blueprint, selected)
 */
abstract class AbstractTreeGenerator extends AbstractSpriteGenerator
{
    public function shouldGenerateStates(): bool
    {
        return false;
    }

    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality';
    }
}
