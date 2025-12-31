<?php

namespace bl\entity\generators\tree;

use bl\entity\generators\base\AbstractEntityGenerator;

/**
 * Base class for tree generators
 * Trees don't have states (damaged, blueprint, selected)
 */
abstract class AbstractTreeGenerator extends AbstractEntityGenerator
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
