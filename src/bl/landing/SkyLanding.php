<?php

namespace bl\landing;

use bl\landing\generators\sky\SkyLandingGenerator;

/**
 * Sky landing type - sky background under the floating island, not buildable
 */
class SkyLanding extends AbstractSkyLanding
{
    public const LANDING_ID = 9;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
