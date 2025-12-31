<?php

namespace bl\entity\types\relief;

use bl\entity\types\ReliefEntityType;

/**
 * Small rock entity type
 */
class SmallRockEntityType extends ReliefEntityType
{
    public const ENTITY_TYPE_ID = 10;

    public function getRockSize(): string
    {
        return 'small';
    }
}
