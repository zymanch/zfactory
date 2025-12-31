<?php

namespace bl\entity\generators\building;

class SawmillLargeGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'sawmill_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large industrial sawmill, massive lumber processing facility, automated machinery, log piles, cutting stations, steam-powered saws, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced factory, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
