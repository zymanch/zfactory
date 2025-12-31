<?php

namespace bl\entity\generators\building;

class ChestGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'chest';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'wooden storage chest, game sprite, top-down isometric view, single object, clean white background, photorealistic rendering, realistic wood texture, detailed wood grain, metal fittings, storage container, inventory box, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
