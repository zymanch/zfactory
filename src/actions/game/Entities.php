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
        $entities = $this->castNumericFieldsArray(
            Entity::find()
                ->select(['entity_id', 'entity_type_id', 'state', 'durability', 'x', 'y'])
                ->asArray()
                ->all(),
            ['entity_id', 'entity_type_id', 'durability', 'x', 'y']
        );

        return $this->success(['entities' => $entities]);
    }
}
