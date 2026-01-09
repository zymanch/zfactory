<?php

namespace bl\entity\types\mining;

/**
 * Oil Pump - extracts crude oil from oil deposits (deposit_type_id=20)
 * Output: resource_id=301 (Crude Oil)
 */
class OilPumpEntityType extends FluidPumpEntityType
{
    public const ENTITY_TYPE_ID = 146;

    public function getOutputResourceId(): int
    {
        return 301; // Crude Oil
    }

    public function canPlaceAt(?int $landingId, ?int $depositTypeId): bool
    {
        return $depositTypeId === 20; // Oil well deposit
    }
}
