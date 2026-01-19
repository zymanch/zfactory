<?php

namespace bl\entity\sprite_generators\building;

class StabilizerSmallGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'stabilizer_small';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'small compact stabilizer device, 1x1 platform, hexagonal energy field emitter, blue glowing core, metallic framework, sci-fi industrial building, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic metal textures, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
