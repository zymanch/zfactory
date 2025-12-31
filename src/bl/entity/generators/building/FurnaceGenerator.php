<?php

namespace bl\entity\generators\building;

class FurnaceGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'furnace';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'stone furnace, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic stone texture, glowing fire inside, detailed metallic parts, smelting equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
