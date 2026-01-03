<?php

namespace bl\landing;

use bl\landing\generators\ship\ShipFloorWoodLandingGenerator;

/**
 * Ship floor wood landing type - basic wooden deck, buildable
 */
class ShipFloorWoodLanding extends AbstractShipLanding
{
    public const LANDING_ID = 12;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        $factory = new \bl\landing\generators\LandingGeneratorFactory();
        return $factory->getGenerator($this);
    }
}
