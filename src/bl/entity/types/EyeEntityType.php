<?php

namespace bl\entity\types;

/**
 * Base class for eye entity types (decorative structures like crystal towers)
 */
abstract class EyeEntityType extends AbstractEntityType
{
    /**
     * Get entity type category
     */
    public function getTypeCategory(): string
    {
        return 'eye';
    }

    /**
     * Whether this is a decorative-only entity
     */
    public function isDecorative(): bool
    {
        return true;
    }
}
