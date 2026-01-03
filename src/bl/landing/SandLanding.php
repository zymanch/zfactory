<?php

namespace bl\landing;

use bl\landing\generators\island\SandLandingGenerator;

/**
 * Sand landing type - desert/beach, buildable
 */
class SandLanding extends AbstractIslandLanding
{
    public const LANDING_ID = 3;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
