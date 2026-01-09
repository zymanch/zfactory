<?php

namespace bl\entity\types\mining;

/**
 * Gas Pump - extracts natural gas from gas vents (deposit_type_id=21)
 * Output: resource_id=302 (Natural Gas)
 */
class GasPumpEntityType extends FluidPumpEntityType
{
    public const ENTITY_TYPE_ID = 147;

    public function getOutputResourceId(): int
    {
        return 302; // Natural Gas
    }

    public function canPlaceAt(?int $landingId, ?int $depositTypeId): bool
    {
        return $depositTypeId === 21; // Gas vent deposit
    }
}
