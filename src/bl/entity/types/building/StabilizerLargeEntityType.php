<?php

namespace bl\entity\types\building;

use bl\entity\types\BuildingEntityType;

/**
 * Large Stabilizer (3x3, radius 20 tiles)
 * Consumes Stability resource to protect buildings from shake damage
 */
class StabilizerLargeEntityType extends BuildingEntityType
{
    public const ENTITY_TYPE_ID = 952;
}
