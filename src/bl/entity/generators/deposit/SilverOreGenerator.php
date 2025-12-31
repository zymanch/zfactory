<?php

namespace bl\entity\generators\deposit;

class SilverOreGenerator extends AbstractDepositGenerator
{
    public function getImageUrl(): string
    {
        return 'ore_silver';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'scattered silver ore rocks, pile of silver ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, shiny silver-white stones, precious metal ore, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art';
    }
}
