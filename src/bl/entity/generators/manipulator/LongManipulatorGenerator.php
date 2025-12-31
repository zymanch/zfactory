<?php

namespace bl\entity\generators\manipulator;

class LongManipulatorGenerator extends AbstractManipulatorGenerator
{
    public function getImageUrl(): string
    {
        return 'manipulator_long';
    }

    public function getFluxPositivePrompt(): string
    {
        return 'long robotic arm manipulator, game sprite, top-down isometric view, single object, clean white background, photorealistic industrial rendering, realistic metal textures, extended mechanical arm, gripper claw, industrial robot, NOT tilted, NOT angled, straight top-down perspective, highly detailed, realistic lighting, game asset, professional quality, no shadows';
    }
}
