<?php

namespace bl\landing\generators\ship;

/**
 * Generator for crystal ship floor landing sprites
 * Magical crystal floor for end-game/mystical ships
 */
class ShipFloorCrystalLandingGenerator extends AbstractShipLandingGenerator
{
    public function getFolder(): string
    {
        return 'ship_floor_crystal';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, purple glowing crystals embedded in metal floor, magical energy floor, sci-fi mystical surface, luminescent crystals, top-down orthographic view, game texture';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, side view, perspective, rocks, dirt, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'bright glow',
            'dim crystals',
            'large crystals',
            'small crystals',
        ];
    }
}
