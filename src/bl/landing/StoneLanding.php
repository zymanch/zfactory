<?php

namespace bl\landing;

use bl\landing\generators\island\StoneLandingGenerator;

/**
 * Stone landing type - gray rocky terrain, not buildable
 */
class StoneLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 5;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        return new StoneLandingGenerator();
    }
}
