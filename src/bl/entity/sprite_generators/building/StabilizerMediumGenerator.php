<?php

namespace bl\entity\sprite_generators\building;

class StabilizerMediumGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'stabilizer_medium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'medium-sized stabilizer platform, 2x2 industrial structure, hexagonal energy field projector, blue glowing energy core, reinforced metallic framework, sci-fi industrial building, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic metal textures, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
