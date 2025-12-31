<?php

namespace bl\entity\generators\building;

use bl\entity\generators\base\AbstractEntityGenerator;

/**
 * Base class for building generators
 * Provides common negative prompt for all buildings
 */
abstract class AbstractBuildingGenerator extends AbstractEntityGenerator
{
    /**
     * Common negative prompt for all buildings
     */
    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, tilted, angled view, 45 degree angle, diagonal, perspective distortion, isometric base, diamond base, platform, foundation, pedestal, multiple objects, landscape, ground, blurry, low quality';
    }
}
