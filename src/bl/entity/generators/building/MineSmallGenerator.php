<?php

namespace bl\entity\generators\building;

class MineSmallGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'mine_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'small mine entrance, mineshaft with support beams, mining cart tracks, precious metal extraction, compact mining station, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood and stone textures, industrial building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
