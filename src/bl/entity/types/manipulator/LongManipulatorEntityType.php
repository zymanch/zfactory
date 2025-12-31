<?php

namespace bl\entity\types\manipulator;

use bl\entity\types\ManipulatorEntityType;

/**
 * Long manipulator entity type - picks up items from further away
 */
class LongManipulatorEntityType extends ManipulatorEntityType
{
    public const ENTITY_TYPE_ID = 201;

    public function getReachDistance(): int
    {
        return 2;
    }
}
