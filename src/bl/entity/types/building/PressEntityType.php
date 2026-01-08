<?php

namespace bl\entity\types\building;

use bl\entity\types\BuildingEntityType;

/**
 * Press entity type - creates intermediate components (rods, plates, wires, screws, bolts) from ingots
 */
class PressEntityType extends BuildingEntityType
{
    public const ENTITY_TYPE_ID = 115;
}
