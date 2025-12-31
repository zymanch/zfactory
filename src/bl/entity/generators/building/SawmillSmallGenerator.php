<?php

namespace bl\entity\generators\building;

class SawmillSmallGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'sawmill_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'small wooden sawmill building, lumber mill, wood processing facility, rustic workshop, saws and logs visible, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, industrial building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
