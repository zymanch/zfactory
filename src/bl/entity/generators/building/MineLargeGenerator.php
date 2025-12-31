<?php

namespace bl\entity\generators\building;

class MineLargeGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'mine_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large mining complex, deep shaft entrance, ore sorting facility, elevator system, industrial mine buildings, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic industrial textures, advanced mining operation, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
