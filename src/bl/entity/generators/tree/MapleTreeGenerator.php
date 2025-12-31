<?php

namespace bl\entity\generators\tree;

class MapleTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_maple';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'maple tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, broad leafy canopy, distinctive maple leaves, brown trunk, highly detailed bark, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall';
    }
}
