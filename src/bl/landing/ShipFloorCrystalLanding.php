<?php

namespace bl\landing;

use bl\landing\generators\ship\ShipFloorCrystalLandingGenerator;

/**
 * Ship floor crystal landing type - magical crystal floor, buildable
 */
class ShipFloorCrystalLanding extends AbstractShipLanding
{
    public const LANDING_ID = 16;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
