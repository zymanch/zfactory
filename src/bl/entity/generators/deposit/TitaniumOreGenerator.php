<?php

namespace bl\entity\generators\deposit;

class TitaniumOreGenerator extends AbstractDepositGenerator
{
    public function getImageUrl(): string
    {
        return 'ore_titanium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'scattered titanium ore rocks, pile of titanium ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, dark metallic gray stones, ilmenite rocks, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art';
    }
}
