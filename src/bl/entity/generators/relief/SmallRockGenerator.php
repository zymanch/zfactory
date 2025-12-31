<?php

namespace bl\entity\generators\relief;

class SmallRockGenerator extends AbstractReliefGenerator
{
    public function getImageUrl(): string
    {
        return 'rock_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'small rock, game sprite, isometric view, single object, clean white background, photorealistic rendering, realistic stone texture, gray boulder, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
