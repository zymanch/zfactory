<?php

namespace bl\entity\sprite_generators\base;

use models\EntityType;

/**
 * Interface for entity sprite generators
 * Generators produce ONLY sprite.png and animation.png (if animated)
 * They do NOT generate atlases - that's done by atlas generators
 */
interface SpriteGeneratorInterface
{
    /**
     * Generate base sprite (sprite.png) for entity type
     * @param EntityType $entityType
     * @param bool $testMode If true, only generate minimal output for testing
     * @return bool Success status
     */
    public function generate(EntityType $entityType, bool $testMode = false): bool;

    /**
     * Generate state variants (damaged, blueprint, selected, etc.)
     * @param EntityType $entityType
     * @return bool Success status
     */
    public function generateStates(EntityType $entityType): bool;

    /**
     * Check if this generator supports given entity type
     * @param EntityType $entityType
     * @return bool
     */
    public function supports(EntityType $entityType): bool;
}
