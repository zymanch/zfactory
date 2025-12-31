<?php

namespace bl\entity\generators\eye;

class LargeCrystalTowerGenerator extends AbstractEyeGenerator
{
    public function getImageUrl(): string
    {
        return 'crystal_tower_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large crystal tower, massive magical glowing crystal formation, game sprite, isometric view, single object, clean white background, photorealistic rendering, purple crystals, ethereal glow, mystical structure, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
