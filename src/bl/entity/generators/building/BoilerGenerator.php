<?php

namespace bl\entity\generators\building;

class BoilerGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'boiler';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'water boiler, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, detailed rivets and pipes, steam generator, factory equipment, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
