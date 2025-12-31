<?php

namespace bl\entity\generators\building;

class StoneQuarryMediumGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'stone_quarry_medium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'medium stone quarry, crushing plant, conveyor belts for rocks, stone storage piles, industrial rock processing, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, mining complex, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
