<?php

namespace bl\landing;

use bl\landing\generators\ship\ShipFloorIronLandingGenerator;

/**
 * Ship floor iron landing type - industrial iron floor, buildable
 */
class ShipFloorIronLanding extends AbstractShipLanding
{
    public const LANDING_ID = 13;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
