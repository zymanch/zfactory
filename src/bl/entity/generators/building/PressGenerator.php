<?php

namespace bl\entity\generators\building;

class PressGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'press';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'hydraulic press machine, industrial press, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, heavy machinery, pressing mechanism, vertical hydraulic cylinder, pressing plate, solid metal frame, factory equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
