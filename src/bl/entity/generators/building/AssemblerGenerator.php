<?php

namespace bl\entity\generators\building;

class AssemblerGenerator extends AbstractBuildingGenerator
{
    public function getImageUrl(): string
    {
        return 'assembler';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'assembly machine, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, complex machinery, detailed mechanical parts, crafting machine, NOT tilted, NOT angled, straight top-down perspective, flat orientation, no base platform, no isometric foundation, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
