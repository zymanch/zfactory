<?php

namespace bl\entity\generators\building;

class StoneQuarryLargeGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'stone_quarry_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large industrial stone quarry, massive rock crusher, sorting facilities, storage silos, automated processing lines, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced mining facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
