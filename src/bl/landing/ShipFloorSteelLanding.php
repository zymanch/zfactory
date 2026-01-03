<?php

namespace bl\landing;

use bl\landing\generators\ship\ShipFloorSteelLandingGenerator;

/**
 * Ship floor steel landing type - polished steel floor, buildable
 */
class ShipFloorSteelLanding extends AbstractShipLanding
{
    public const LANDING_ID = 14;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
