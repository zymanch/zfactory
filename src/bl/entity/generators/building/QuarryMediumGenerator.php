<?php

namespace bl\entity\generators\building;

class QuarryMediumGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'quarry_medium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'medium quarry complex, rare metal extraction, aluminum and titanium processing, industrial facility, conveyor systems, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, mining facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
