<?php

namespace bl\landing;

use bl\landing\generators\ship\ShipEdgeLandingGenerator;

/**
 * Ship edge landing type - ship hull side edge, not buildable
 */
class ShipEdgeLanding extends AbstractShipLanding
{
    public const LANDING_ID = 11;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
