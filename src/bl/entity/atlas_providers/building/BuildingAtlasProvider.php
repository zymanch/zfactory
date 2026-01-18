<?php

namespace bl\entity\atlas_providers\building;

use bl\entity\atlas_providers\base\AbstractAtlasProvider;
use bl\entity\atlas_generators\EntityAtlasGenerator;
use bl\entity\sprite_generators\base\ImageProcessor;
use models\EntityType;

/**
 * Atlas provider for building type entities
 *
 * Buildings use a single atlas.png file containing:
 * - Row 1: 7 state sprites (normal, damaged, blueprint, etc.)
 * - Row 2: 9 construction frames (10%-90%)
 *
 * Source sprite: sprite.png (or fallback to normal.png if not migrated yet)
 */
class BuildingAtlasProvider extends AbstractAtlasProvider
{
    /**
     * Get atlas generators for building entity
     * @param EntityType $entityType
     * @return array<string, \bl\entity\atlas_generators\base\AtlasGeneratorInterface>
     */
    public function getAtlasGenerators(EntityType $entityType): array
    {
        $sprite = $this->getSourceSprite($entityType);

        return [
            'atlas' => new EntityAtlasGenerator($sprite, $entityType->width, $entityType->height)
        ];
    }

    /**
     * Get source sprite (sprite.png) as GD resource
     * @param EntityType $entityType
     * @return resource GD image resource
     * @throws \Exception If sprite not found
     */
    public function getSourceSprite(EntityType $entityType)
    {
        // Priority 1: sprite.png (new structure)
        $spritePath = $this->getSpritePath($entityType, 'sprite.png');
        if (file_exists($spritePath)) {
            $sprite = $this->loadSpriteFromFile($spritePath);
            if ($sprite) {
                return $sprite;
            }
        }

        // Priority 2: normal.png (old structure, before migration)
        $normalPath = $this->getSpritePath($entityType, 'normal.png');
        if (file_exists($normalPath)) {
            $sprite = $this->loadSpriteFromFile($normalPath);
            if ($sprite) {
                return $sprite;
            }
        }

        // Priority 3: If orientation variant, rotate from parent
        if ($entityType->parent_entity_type_id) {
            $parent = EntityType::findOne($entityType->parent_entity_type_id);
            if ($parent) {
                // Try parent's sprite.png
                $parentSpritePath = $this->getSpritePath($parent, 'sprite.png');
                if (file_exists($parentSpritePath)) {
                    $parentSprite = $this->loadSpriteFromFile($parentSpritePath);
                    if ($parentSprite) {
                        $angle = $this->getRotationAngle($entityType->orientation);
                        $rotated = ImageProcessor::rotateImage($parentSprite, $angle);
                        imagedestroy($parentSprite);
                        return $rotated;
                    }
                }

                // Try parent's normal.png
                $parentNormalPath = $this->getSpritePath($parent, 'normal.png');
                if (file_exists($parentNormalPath)) {
                    $parentSprite = $this->loadSpriteFromFile($parentNormalPath);
                    if ($parentSprite) {
                        $angle = $this->getRotationAngle($entityType->orientation);
                        $rotated = ImageProcessor::rotateImage($parentSprite, $angle);
                        imagedestroy($parentSprite);
                        return $rotated;
                    }
                }
            }
        }

        throw new \Exception("Sprite not found for entity_type_id={$entityType->entity_type_id} (folder={$this->getEntityFolder($entityType)})");
    }
}
