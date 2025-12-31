<?php

namespace bl\entity\generators\eye;

class SmallCrystalTowerGenerator extends AbstractEyeGenerator
{
    public function getImageUrl(): string
    {
        return 'crystal_tower_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'small crystal tower, magical glowing crystal, game sprite, isometric view, single object, clean white background, photorealistic rendering, purple crystal, ethereal glow, mystical structure, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
