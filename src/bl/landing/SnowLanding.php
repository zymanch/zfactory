<?php

namespace bl\landing;

use bl\landing\generators\island\SnowLandingGenerator;

/**
 * Snow landing type - white winter terrain, buildable
 */
class SnowLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 7;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
