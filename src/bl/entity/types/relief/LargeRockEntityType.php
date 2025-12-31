<?php

namespace bl\entity\types\relief;

use bl\entity\types\ReliefEntityType;

/**
 * Large rock entity type
 */
class LargeRockEntityType extends ReliefEntityType
{
    public const ENTITY_TYPE_ID = 12;

    public function getRockSize(): string
    {
        return 'large';
    }
}
