<?php

namespace bl\landing;

use bl\landing\generators\island\WaterLandingGenerator;

/**
 * Water landing type - blue water, not buildable
 */
class WaterLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 4;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
