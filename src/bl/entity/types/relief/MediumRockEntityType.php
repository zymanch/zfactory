<?php

namespace bl\entity\types\relief;

use bl\entity\types\ReliefEntityType;

/**
 * Medium rock entity type
 */
class MediumRockEntityType extends ReliefEntityType
{
    public const ENTITY_TYPE_ID = 11;

    public function getRockSize(): string
    {
        return 'medium';
    }
}
