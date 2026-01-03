<?php

namespace bl\landing\generators\ship;

/**
 * Generator for titanium ship floor landing sprites
 * Futuristic titanium floor for high-tech ships
 */
class ShipFloorTitaniumLandingGenerator extends AbstractShipLandingGenerator
{
    public function getFolder(): string
    {
        return 'ship_floor_titanium';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, blue-gray titanium alloy plates, futuristic sci-fi floor, advanced metal surface, top-down orthographic view, game texture';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, side view, perspective, damaged, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'blue tint',
            'purple tint',
            'futuristic glow',
            'advanced alloy',
        ];
    }
}
