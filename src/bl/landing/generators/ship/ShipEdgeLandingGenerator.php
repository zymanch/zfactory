<?php

namespace bl\landing\generators\ship;

/**
 * Generator for ship edge landing sprites
 * Ship edge shows the side of the spaceship hull
 */
class ShipEdgeLandingGenerator extends AbstractShipLandingGenerator
{
    public function getFolder(): string
    {
        return 'ship_edge';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'seamless tileable texture, dark metal ship hull side, industrial panels with rivets, sci-fi starship exterior, side view, metallic surface, game texture';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'blurry, low quality, top-down view, windows, interior, grass, text, watermark';
    }

    public function getVariationPrompts(): array
    {
        return [
            'more rivets',
            'darker metal',
            'lighter panels',
            'weathered surface',
        ];
    }

    /**
     * @inheritDoc
     * Ship edge has transparent bottom like island edge
     */
    public function shouldMakeBottomTransparent(): bool
    {
        return true;
    }

    public function getBottomTransparencyHeight(): float
    {
        return 0.5;
    }
}
