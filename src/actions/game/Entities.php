<?php

namespace actions\game;

use actions\JsonAction;
use models\Entity;

/**
 * AJAX: Load all entities (called once on init)
 */
class Entities extends JsonAction
{
    public function run()
    {
        // Get current region ID
        $currentRegionId = 1; // Default
        if (!$this->isGuest()) {
            $currentRegionId = (int)$this->getUser()->current_region_id;
        }

        // Filter entities by current region
        $entities = $this->castNumericFieldsArray(
            Entity::find()
                ->select(['entity_id', 'entity_type_id', 'state', 'durability', 'x', 'y'])
                ->where(['region_id' => $currentRegionId])
                ->asArray()
                ->all(),
            ['entity_id', 'entity_type_id', 'durability', 'x', 'y']
        );

        return $this->success(['entities' => $entities]);
    }
}
