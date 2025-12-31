<?php

namespace bl\entity\generators\tree;

class SpruceTreeGenerator extends AbstractTreeGenerator
{
    public function getImageUrl(): string
    {
        return 'tree_spruce';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'spruce tree, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic textures, conical shape, dense green needles, brown trunk, tall evergreen, highly detailed, realistic lighting, game asset, professional quality, no shadows, height 2-3 tiles tall';
    }
}
