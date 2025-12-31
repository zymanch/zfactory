<?php

namespace bl\entity\generators\relief;

class LargeRockGenerator extends AbstractReliefGenerator
{
    public function getImageUrl(): string
    {
        return 'rock_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large rock, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, gray boulder, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
