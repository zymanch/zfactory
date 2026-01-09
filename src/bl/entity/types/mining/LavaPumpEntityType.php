<?php

namespace bl\entity\types\mining;

/**
 * Lava Pump - extracts lava from lava landing (landing_id=6)
 * Output: resource_id=303 (Lava)
 */
class LavaPumpEntityType extends FluidPumpEntityType
{
    public const ENTITY_TYPE_ID = 148;

    public function getOutputResourceId(): int
    {
        return 303; // Lava
    }

    public function canPlaceAt(?int $landingId, ?int $depositTypeId): bool
    {
        return $landingId === 6; // Lava landing
    }
}
