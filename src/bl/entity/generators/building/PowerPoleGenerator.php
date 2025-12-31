<?php

namespace bl\entity\generators\building;

class PowerPoleGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'power_pole';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'wooden power pole, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, detailed wood grain, electricity pole with insulators, power tower, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
