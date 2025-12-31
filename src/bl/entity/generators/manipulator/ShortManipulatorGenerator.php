<?php

namespace bl\entity\generators\manipulator;

class ShortManipulatorGenerator extends AbstractManipulatorGenerator
{
    public function getImageUrl(): string
    {
        return 'manipulator_short';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'short robotic arm manipulator, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, compact mechanical arm, gripper claw, industrial robot, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
