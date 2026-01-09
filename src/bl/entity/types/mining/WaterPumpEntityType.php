<?php

namespace bl\entity\types\mining;

/**
 * Water Pump - extracts water from water landing (landing_id=4)
 * Output: resource_id=300 (Water)
 */
class WaterPumpEntityType extends FluidPumpEntityType
{
    public const ENTITY_TYPE_ID = 145;

    public function getOutputResourceId(): int
    {
        return 300; // Water
    }

    public function canPlaceAt(?int $landingId, ?int $depositTypeId): bool
    {
        return $landingId === 4; // Water landing
    }
}
