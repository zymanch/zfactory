<?php

namespace bl\landing;

use bl\landing\generators\island\DirtLandingGenerator;

/**
 * Dirt landing type - brown path, buildable
 */
class DirtLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 2;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        return new DirtLandingGenerator();
    }
}
