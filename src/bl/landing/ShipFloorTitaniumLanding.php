<?php

namespace bl\landing;

use bl\landing\generators\ship\ShipFloorTitaniumLandingGenerator;

/**
 * Ship floor titanium landing type - futuristic titanium floor, buildable
 */
class ShipFloorTitaniumLanding extends AbstractShipLanding
{
    public const LANDING_ID = 15;

    public function getGenerator(): ?\bl\landing\generators\base\AbstractLandingGenerator
    {
        return new \bl\landing\generators\ship\ShipFloorTitaniumLandingGenerator();
    }
}
