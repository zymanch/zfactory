<?php

namespace bl\landing;

use bl\landing\generators\island\LavaLandingGenerator;

/**
 * Lava landing type - red/orange hazard, not buildable
 */
class LavaLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 6;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
