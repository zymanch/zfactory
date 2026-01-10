<?php

namespace bl\entity\types;

/**
 * Base class for electricity entity types (pylons, batteries, generators)
 */
abstract class ElectricityEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'electricity';
    }

    /**
     * Get power radius for pylons (0 for batteries and generators)
     */
    public function getPowerRadius(): int
    {
        if ($this->isPylon()) {
            return $this->power ?? 0;
        }
        return 0;
    }

    /**
     * Get storage capacity for batteries (0 for pylons and generators)
     */
    public function getStorageCapacity(): int
    {
        if ($this->isBattery()) {
            return $this->power ?? 0;
        }
        return 0;
    }

    /**
     * Get production rate for generators (0 for pylons and batteries)
     */
    public function getProductionRate(): int
    {
        if ($this->isGenerator()) {
            return $this->power ?? 0;
        }
        return 0;
    }

    /**
     * Check if this is a pylon (900-902)
     */
    public function isPylon(): bool
    {
        return $this->entity_type_id >= 900 && $this->entity_type_id <= 902;
    }

    /**
     * Check if this is a battery (910-912)
     */
    public function isBattery(): bool
    {
        return $this->entity_type_id >= 910 && $this->entity_type_id <= 912;
    }

    /**
     * Check if this is a generator (920-922)
     */
    public function isGenerator(): bool
    {
        return $this->entity_type_id >= 920 && $this->entity_type_id <= 922;
    }

    /**
     * Get electricity role for system
     * @return string pylon|battery|generator|consumer
     */
    public function getElectricityRole(): string
    {
        if ($this->isPylon()) {
            return 'pylon';
        }
        if ($this->isBattery()) {
            return 'battery';
        }
        if ($this->isGenerator()) {
            return 'generator';
        }
        return 'consumer';
    }
}
