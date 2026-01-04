<?php

namespace bl\landing;

use bl\landing\generators\island\SwampLandingGenerator;

/**
 * Swamp landing type - dark green marsh, not buildable
 */
class SwampLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 8;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        return new \bl\landing\generators\island\SwampLandingGenerator();
    }
}
