<?php

namespace bl\entity\generators\deposit;

class GoldOreGenerator extends AbstractDepositGenerator
{
    public function getImageUrl(): string
    {
        return 'ore_gold';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'scattered gold ore rocks, pile of gold ore filling entire space, many ore chunks, game asset, isometric top-down view, 2D game sprite, painted style, golden-yellow stones, precious metal ore, resource node, fully visible rocks not cropped, maximum coverage, clean design, professional game art';
    }
}
