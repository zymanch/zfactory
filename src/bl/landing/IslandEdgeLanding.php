<?php

namespace bl\landing;

use bl\landing\generators\sky\IslandEdgeLandingGenerator;

/**
 * Island edge landing type - floating island bottom edge (stalactites), not buildable
 */
class IslandEdgeLanding extends AbstractSkyLanding
{
    public const LANDING_ID = 10;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        return new \bl\landing\generators\sky\IslandEdgeLandingGenerator();
    }
}
