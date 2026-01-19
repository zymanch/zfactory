<?php

namespace bl\entity\sprite_generators\building;

class StabilizerLargeGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'stabilizer_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large stabilizer installation, 3x3 industrial platform, massive hexagonal energy field generator, multiple blue glowing energy cores, heavy-duty metallic framework, sci-fi industrial building, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic metal textures, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
