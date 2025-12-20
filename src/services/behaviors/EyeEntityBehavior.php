<?php

namespace services\behaviors;

use models\Entity;

/**
 * Behavior for eye entities (Crystal Towers)
 *
 * Eye entities are:
 * - Buildable with standard rules
 * - Show hover info
 * - Destructible
 * - Provide visibility radius
 */
class EyeEntityBehavior extends DefaultEntityBehavior
{
    /**
     * Eye entities use default building rules
     * They inherit from DefaultEntityBehavior
     */

    /**
     * Eye entities should show hover info (power/radius info)
     */
    public function shouldShowHoverInfo(): bool
    {
        return true;
    }

    /**
     * Eye entities are destructible
     */
    public function isIndestructible(): bool
    {
        return false;
    }

    /**
     * Get client info including visibility radius
     */
    public function getClientInfo(): array
    {
        return array_merge(parent::getClientInfo(), [
            'providesVisibility' => true,
            'visibilityRadius' => $this->entityType->power ?? 1,
        ]);
    }
}
