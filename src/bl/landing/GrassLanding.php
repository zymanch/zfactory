<?php

namespace bl\landing;

use bl\landing\generators\island\GrassLandingGenerator;

/**
 * Grass landing type - basic green terrain, buildable
 */
class GrassLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 1;

    public function getGenerator(): ?AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
