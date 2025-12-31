<?php

namespace bl\entity\generators\deposit;

class AluminumOreGenerator extends AbstractDepositGenerator
{
    public function getImageUrl(): string
    {
        return 'ore_aluminum';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'scattered aluminum ore rocks, pile of aluminum ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, light silver-gray stones, bauxite rocks, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art';
    }
}
