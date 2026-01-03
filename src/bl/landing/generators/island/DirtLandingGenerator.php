<?php

namespace bl\landing\generators\island;

/**
 * Generator for dirt landing sprites
 */
class DirtLandingGenerator extends AbstractIslandLandingGenerator
{
    public function getFolder(): string
    {
        return 'dirt';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, brown dirt ground, earth soil, top-down view, game texture, natural, simple';
    }

    public function getVariationPrompts(): array
    {
        return [
            'small rocks',
            'darker',
            'lighter',
            'cracks',
        ];
    }
}
