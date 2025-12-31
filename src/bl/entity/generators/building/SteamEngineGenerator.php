<?php

namespace bl\entity\generators\building;

class SteamEngineGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'steam_engine';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'steam engine turbine, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, power generator, factory building, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
