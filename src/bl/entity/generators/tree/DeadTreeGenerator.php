<?php

namespace bl\entity\generators\tree;

class DeadTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_dead';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'dead tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, bare branches, gray trunk, withered, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, leaves, multiple objects, landscape, ground, grass, rocks, sky, blurry, low quality';
    }
}
