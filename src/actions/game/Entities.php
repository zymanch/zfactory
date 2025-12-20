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
        $entities = Entity::find()
            ->select(['entity_id', 'entity_type_id', 'state', 'durability', 'x', 'y'])
            ->asArray()
            ->all();

        return $this->success(['entities' => $entities]);
    }
}
