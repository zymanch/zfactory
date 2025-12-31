<?php

namespace bl\entity\generators\tree;

class WillowTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_willow';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'willow tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, drooping branches, green hanging foliage, brown trunk, tall tree, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall';
    }
}
