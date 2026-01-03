<?php

namespace bl\landing\generators\ship;

/**
 * Generator for steel ship floor landing sprites
 * Polished steel floor for advanced ships
 */
class ShipFloorSteelLandingGenerator extends AbstractShipLandingGenerator
{
    public function getFolder(): string
    {
        return 'ship_floor_steel';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, light gray polished steel plates, clean industrial metal floor, smooth metallic surface, top-down orthographic view, game texture';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, side view, perspective, dirty, rust, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'polished shine',
            'brushed finish',
            'light scratches',
            'pristine clean',
        ];
    }
}
