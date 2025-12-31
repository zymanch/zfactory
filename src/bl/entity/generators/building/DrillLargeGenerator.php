<?php

namespace bl\entity\generators\building;

class DrillLargeGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'drill_large';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'large mining drill, heavy industrial drilling rig, automated ore extraction, powerful machinery, metal framework, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic metal textures, advanced mining equipment, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
