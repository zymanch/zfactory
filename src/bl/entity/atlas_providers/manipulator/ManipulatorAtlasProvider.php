<?php

namespace bl\entity\atlas_providers\manipulator;

use bl\entity\atlas_providers\base\AbstractAtlasProvider;
use bl\entity\atlas_generators\ManipulatorAtlasGenerator;
use models\EntityType;

/**
 * Atlas provider for manipulator type entities
 *
 * Manipulators use a single multi-row atlas (animation_atlas.png):
 * - Row 0: normal (all frames)
 * - Row 1: damaged (all frames)
 * - Row 2: blueprint (all frames)
 * - Row 3: normal_selected (all frames)
 * - Row 4: damaged_selected (all frames)
 * - Rows 5-13: construction progress 10%-90% (center frame only)
 *
 * Source sprite: animation.png (frameCount frames horizontal, 1 row)
 * For rotated variants (up/down/left), rotates from parent entity's animation.png
 */
class ManipulatorAtlasProvider extends AbstractAtlasProvider
{
    /**
     * Get atlas generators for manipulator entity
     * @param EntityType $entityType
     * @return array<string, \bl\entity\atlas_generators\base\AtlasGeneratorInterface>
     */
    public function getAtlasGenerators(EntityType $entityType): array
    {
        // Load animation.png (each orientation has its own pre-generated animation)
        $animationGd = $this->getSourceSprite($entityType);

        // No rotation - each manipulator has its own animation.png
        return [
            'animation_atlas' => new ManipulatorAtlasGenerator(
                $animationGd,
                $entityType,
                0  // No rotation
            )
        ];
    }

    /**
     * Get source animation (animation.png) as GD resource
     * @param EntityType $entityType
     * @return resource GD image resource
     * @throws \Exception If animation not found
     */
    public function getSourceSprite(EntityType $entityType)
    {
        // Each manipulator orientation has its own pre-generated animation.png
        $animationPath = $this->getSpritePath($entityType, 'animation.png');
        if (file_exists($animationPath)) {
            $animation = $this->loadSpriteFromFile($animationPath);
            if ($animation) {
                return $animation;
            }
        }

        throw new \Exception("animation.png not found for entity_type_id={$entityType->entity_type_id} (folder={$this->getEntityFolder($entityType)})");
    }
}
