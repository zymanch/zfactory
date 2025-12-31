<?php

namespace bl\entity\generators\building;

class StoneQuarrySmallGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'stone_quarry_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'small stone quarry, rock crushing machine, stone processing station, mining equipment, gravel pit, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic stone and metal textures, industrial facility, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
