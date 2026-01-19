<?php

namespace bl\entity\types\building;

use bl\entity\types\BuildingEntityType;

/**
 * Medium Stabilizer (2x2, radius 10 tiles)
 * Consumes electricity to protect buildings from shake damage
 */
class StabilizerMediumEntityType extends BuildingEntityType
{
    public const ENTITY_TYPE_ID = 951;
}
