<?php

namespace bl\entity\generators\building;

class QuarrySmallGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'quarry_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'small quarry for rare ores, aluminum titanium extraction, compact mining facility, modern equipment, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic metal and stone textures, industrial building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
