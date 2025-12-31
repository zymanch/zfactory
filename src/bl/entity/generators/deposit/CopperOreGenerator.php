<?php

namespace bl\entity\generators\deposit;

class CopperOreGenerator extends AbstractDepositGenerator
{
    public function getImageUrl(): string
    {
        return 'ore_copper';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'scattered copper ore rocks, pile of copper ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, orange-brown stones, copper ore cluster, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art';
    }
}
