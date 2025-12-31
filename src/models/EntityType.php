<?php

namespace models;

use bl\entity\types\EntityTypeFactory;
use models\base;

class EntityType extends base\BaseEntityType
{
    /**
     * @inheritdoc
     * Creates an instance of the appropriate EntityType subclass based on entity_type_id
     */
    public static function instantiate($row)
    {
        $entityTypeId = $row['entity_type_id'] ?? null;

        if ($entityTypeId !== null) {
            $class = EntityTypeFactory::getClass((int)$entityTypeId);
            if ($class !== null) {
                return new $class();
            }
        }

        return new static();
    }
}