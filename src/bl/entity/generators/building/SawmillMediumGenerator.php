<?php

namespace bl\entity\generators\building;

class SawmillMediumGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'sawmill_medium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'medium sawmill complex, large lumber mill, multiple saws, log storage area, conveyor belts, industrial wood processing, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood and metal textures, factory building, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
