<?php

namespace bl\entity\types\manipulator;

use bl\entity\types\ManipulatorEntityType;

/**
 * Short manipulator entity type - picks up items from nearby
 */
class ShortManipulatorEntityType extends ManipulatorEntityType
{
    public const ENTITY_TYPE_ID = 200;

    public function getReachDistance(): int
    {
        return 1;
    }
}
