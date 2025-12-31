<?php

namespace bl\entity\generators\deposit;

class IronOreGenerator extends AbstractDepositGenerator
{
    public function getImageUrl(): string
    {
        return 'ore_iron';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'scattered iron ore rocks, pile of iron ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, metallic gray stones, iron ore cluster, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art';
    }
}
