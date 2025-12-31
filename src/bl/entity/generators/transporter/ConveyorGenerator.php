<?php

namespace bl\entity\generators\transporter;

use bl\entity\generators\base\AbstractEntityGenerator;

class ConveyorGenerator extends AbstractEntityGenerator
{
    public function getImageUrl(): string
    {
        return 'conveyor';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'flat conveyor belt, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal texture, gray metallic belt, detailed mechanical parts, no legs, no stand, no support structure, PERFECTLY HORIZONTAL ORIENTATION, straight from left to right, NOT diagonal, NOT angled, belt running horizontally across image, flat on surface, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }

    public function getFluxNegativePrompt(): string
    {
        return 'cartoon, anime, stylized, simplified, flat shading, cel shaded, legs, stand, support structure, elevated, platform, base, diagonal angle, angled view, tilted, rotated, multiple objects, landscape, ground, blurry, low quality';
    }

    public function isRotational(): bool
    {
        return true;
    }
}
