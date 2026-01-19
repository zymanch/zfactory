<?php

namespace bl\entity\types\building;

use bl\entity\types\BuildingEntityType;

/**
 * Small Stabilizer (1x1, radius 5 tiles)
 * Consumes Stability resource to protect buildings from shake damage
 */
class StabilizerSmallEntityType extends BuildingEntityType
{
    public const ENTITY_TYPE_ID = 950;
}
