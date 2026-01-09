<?php

namespace bl\entity\types\mining;

use bl\entity\types\AbstractEntityType;

/**
 * Base class for fluid pump entity types
 * Pumps extract fluids from landing/deposits and push them into connected pipes
 */
abstract class FluidPumpEntityType extends AbstractEntityType
{
    /**
     * Get output resource ID (fluid type)
     * @return int
     */
    abstract public function getOutputResourceId(): int;

    /**
     * Get extraction rate (amount of fluid per tick)
     * Based on power value: power/100 = units per tick
     * @return int
     */
    public function getExtractionRate(): int
    {
        return (int)($this->power / 100);
    }

    /**
     * Get type category
     * @return string
     */
    public function getTypeCategory(): string
    {
        return 'mining';
    }

    /**
     * Check if this pump can be placed at given landing/deposit
     * @param int|null $landingId
     * @param int|null $depositTypeId
     * @return bool
     */
    abstract public function canPlaceAt(?int $landingId, ?int $depositTypeId): bool;
}
