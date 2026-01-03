<?php

namespace bl\landing\generators\ship;

/**
 * Generator for iron ship floor landing sprites
 * Industrial iron floor for mid-game ships
 */
class ShipFloorIronLandingGenerator extends AbstractShipLandingGenerator
{
    public function getFolder(): string
    {
        return 'ship_floor_iron';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, dark gray iron metal plates, industrial floor panels with rivets, heavy duty metal surface, top-down orthographic view, game texture';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, side view, perspective, rust, damaged, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'more rivets',
            'darker plates',
            'lighter gray',
            'heavy industrial',
        ];
    }
}
