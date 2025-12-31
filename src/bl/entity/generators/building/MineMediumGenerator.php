<?php

namespace bl\entity\generators\building;

class MineMediumGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'mine_medium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'medium mine complex, multiple shafts, ore processing area, mining carts, support structures, industrial mining facility, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, mining operation, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
