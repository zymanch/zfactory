<?php

namespace bl\entity\atlas_providers\base;

use models\EntityType;

/**
 * Provides atlas generators for entity type
 * Returns array of atlas generators keyed by atlas name
 *
 * Different entity types need different atlases:
 * - Buildings: one atlas.png with states + construction frames
 * - Conveyors: multiple atlases (normal_atlas, damaged_atlas, etc.)
 * - Pipes: multiple state atlases
 *
 * Provider decides WHICH atlases to generate and delegates to generators.
 */
interface AtlasProviderInterface
{
    /**
     * Get atlas generators for this entity type
     * @param EntityType $entityType
     * @return array<string, \bl\entity\atlas_generators\base\AtlasGeneratorInterface> Key = atlas filename (without .png), Value = generator instance
     *
     * Example for conveyor:
     * [
     *   'normal_atlas' => ConveyorAtlasGenerator($animationGd, 'normal'),
     *   'damaged_atlas' => ConveyorAtlasGenerator($animationGd, 'damaged'),
     *   ...
     * ]
     *
     * Example for building:
     * [
     *   'atlas' => EntityAtlasGenerator($spriteGd, $width, $height)
     * ]
     */
    public function getAtlasGenerators(EntityType $entityType): array;

    /**
     * Get source sprite (sprite.png or animation.png) as GD resource
     * May generate it on-the-fly, rotate from parent, or load from file
     * @param EntityType $entityType
     * @return resource GD image resource
     */
    public function getSourceSprite(EntityType $entityType);
}
