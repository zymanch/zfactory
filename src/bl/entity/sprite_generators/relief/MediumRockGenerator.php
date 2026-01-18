<?php

namespace bl\entity\sprite_generators\relief;

class MediumRockGenerator extends AbstractReliefGenerator
{
    public function getImageUrl(): string
    {
        return 'rock_medium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'medium rock, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, gray boulder, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
