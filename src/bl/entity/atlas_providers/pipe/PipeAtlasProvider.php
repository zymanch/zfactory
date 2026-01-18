<?php

namespace bl\entity\atlas_providers\pipe;

use bl\entity\atlas_providers\base\AbstractAtlasProvider;
use bl\entity\atlas_generators\PipeAtlasGenerator;
use bl\entity\sprite_generators\base\ImageProcessor;
use models\EntityType;

/**
 * Atlas provider for pipe type entities
 *
 * Pipes use multiple atlases (one per state):
 * - pipe_atlas_normal.png
 * - pipe_atlas_damaged.png
 * - pipe_atlas_normal_selected.png
 * - pipe_atlas_damaged_selected.png
 *
 * Each atlas: 16 variants × 1 row = 1024×64px
 *
 * Source sprite: sprite.png
 */
class PipeAtlasProvider extends AbstractAtlasProvider
{
    /**
     * Get atlas generators for pipe entity
     * @param EntityType $entityType
     * @return array<string, \bl\entity\atlas_generators\base\AtlasGeneratorInterface>
     */
    public function getAtlasGenerators(EntityType $entityType): array
    {
        $spriteGd = $this->getSourceSprite($entityType);

        // Pipe needs 4 atlases (one per state)
        $states = ['normal', 'normal_selected', 'damaged', 'damaged_selected'];
        $generators = [];

        foreach ($states as $state) {
            $atlasName = "pipe_atlas_{$state}";
            $generators[$atlasName] = new PipeAtlasGenerator($spriteGd, $state);
        }

        return $generators;
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

        // Fallback: normal.png (old structure)
        $normalPath = $this->getSpritePath($entityType, 'normal.png');
        if (file_exists($normalPath)) {
            $sprite = $this->loadSpriteFromFile($normalPath);
            if ($sprite) {
                return $sprite;
            }
        }

        // If orientation variant, rotate from parent
        if ($entityType->parent_entity_type_id) {
            $parent = EntityType::findOne($entityType->parent_entity_type_id);
            if ($parent) {
                $parentSprite = $this->getSpritePath($parent, 'sprite.png');
                if (!file_exists($parentSprite)) {
                    $parentSprite = $this->getSpritePath($parent, 'normal.png');
                }

                if (file_exists($parentSprite)) {
                    $parentGd = $this->loadSpriteFromFile($parentSprite);
                    if ($parentGd) {
                        $angle = $this->getRotationAngle($entityType->orientation);
                        $rotated = ImageProcessor::rotateImage($parentGd, $angle);
                        imagedestroy($parentGd);
                        return $rotated;
                    }
                }
            }
        }

        throw new \Exception("Sprite not found for entity_type_id={$entityType->entity_type_id} (folder={$this->getEntityFolder($entityType)})");
    }

    /**
     * Get rotation angle for orientation variant
     * @param string|null $orientation Entity orientation (up, down, left, right)
     * @return int Rotation angle in degrees (0, 90, 180, 270)
     */
    protected function getRotationAngle(?string $orientation): int
    {
        if (!$orientation) {
            return 0;
        }

        switch ($orientation) {
            case 'up':
                return 270;
            case 'down':
                return 90;
            case 'left':
                return 180;
            case 'right':
                return 0;
            default:
                return 0;
        }
    }
}
