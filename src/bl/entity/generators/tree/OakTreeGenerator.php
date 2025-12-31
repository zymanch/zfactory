<?php

namespace bl\entity\generators\tree;

class OakTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_oak';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'oak tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, green leafy canopy, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows';
    }
}
