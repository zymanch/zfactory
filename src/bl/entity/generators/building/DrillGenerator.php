<?php

namespace bl\entity\generators\building;

class DrillGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'drill';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'mining drill, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed mechanical parts, ore extractor, mining equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
