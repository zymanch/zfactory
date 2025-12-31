<?php

namespace bl\entity\generators\tree;

class AshTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_ash';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'ash tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, compound leaves, gray-brown bark, tall trunk, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall';
    }
}
