<?php

namespace bl\entity\generators\tree;

class PineTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_pine';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'pine tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, green pine needles, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows';
    }
}
