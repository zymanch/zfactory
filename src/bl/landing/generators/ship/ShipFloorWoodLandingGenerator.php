<?php

namespace bl\landing\generators\ship;

/**
 * Generator for wooden ship floor landing sprites
 * Basic wooden deck floor for early-game ships
 */
class ShipFloorWoodLandingGenerator extends AbstractShipLandingGenerator
{
    public function getFolder(): string
    {
        return 'ship_floor_wood';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, wooden deck planks, brown wood floor, ship deck, top-down orthographic view, game texture, natural wood grain';
    }

    public function getVariationPrompts(): array
    {
        return [
            'lighter wood',
            'darker wood',
            'worn planks',
            'polished finish',
        ];
    }
}
