<?php

namespace bl\entity\atlas_providers\conveyor;

use bl\entity\atlas_providers\base\AbstractAtlasProvider;
use bl\entity\atlas_generators\ConveyorAtlasGenerator;
use bl\entity\sprite_generators\base\ImageProcessor;
use models\EntityType;

/**
 * Atlas provider for conveyor type entities
 *
 * Conveyors use multiple atlases (one per state):
 * - normal_atlas.png
 * - damaged_atlas.png
 * - blueprint_atlas.png
 * - normal_selected_atlas.png
 * - damaged_selected_atlas.png
 *
 * Each atlas: 16 variants × 8 frames = 1024×512px
 *
 * Source sprite: animation.png (8 frames horizontal)
 * For rotated variants (up/down/left), rotates from parent entity's animation.png
 */
class ConveyorAtlasProvider extends AbstractAtlasProvider
{
    /**
     * Get atlas generators for conveyor entity
     * @param EntityType $entityType
     * @return array<string, \bl\entity\atlas_generators\base\AtlasGeneratorInterface>
     */
    public function getAtlasGenerators(EntityType $entityType): array
    {
        $animationGd = $this->getSourceSprite($entityType);

        // Determine if we need to rotate frames
        $rotationAngle = 0;
        if ($entityType->parent_entity_type_id) {
            $rotationAngle = $this->getRotationAngle($entityType->orientation);
        }

        // Conveyor needs 5 atlases (one per state)
        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
        $generators = [];

        foreach ($states as $state) {
            $atlasName = "{$state}_atlas";
            $generators[$atlasName] = new ConveyorAtlasGenerator(
                $animationGd,
                $state,
                $entityType->orientation ?? 'right',
                $rotationAngle
            );
        }

        return $generators;
    }

    /**
     * Get source animation (animation.png) as GD resource
     * @param EntityType $entityType
     * @return resource GD image resource
     * @throws \Exception If animation not found
     */
    public function getSourceSprite(EntityType $entityType)
    {
        // Priority 1: animation.png (new structure)
        $animationPath = $this->getSpritePath($entityType, 'animation.png');
        if (file_exists($animationPath)) {
            $animation = $this->loadSpriteFromFile($animationPath);
            if ($animation) {
                return $animation;
            }
        }

        // Priority 2: If rotational variant, use parent animation (DON'T rotate yet)
        // Rotation will be applied per-frame in ConveyorAtlasGenerator
        if ($entityType->parent_entity_type_id) {
            $parent = EntityType::findOne($entityType->parent_entity_type_id);
            if ($parent) {
                $parentAnimation = $this->getSpritePath($parent, 'animation.png');

                if (file_exists($parentAnimation)) {
                    $parentGd = $this->loadSpriteFromFile($parentAnimation);
                    if ($parentGd) {
                        return $parentGd; // Return non-rotated animation
                    }
                }
            }
        }

        throw new \Exception("Animation not found for entity_type_id={$entityType->entity_type_id} (folder={$this->getEntityFolder($entityType)})");
    }
}
