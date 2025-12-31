<?php

namespace bl\entity\generators\tree;

class BirchTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_birch';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'birch tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, white bark with black markings, green leafy canopy, slender trunk, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall';
    }
}
