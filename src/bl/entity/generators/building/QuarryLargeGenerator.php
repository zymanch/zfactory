<?php

namespace bl\entity\generators\building;

class QuarryLargeGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'quarry_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large industrial quarry, advanced rare metal extraction, aluminum titanium processing plant, automated systems, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
