<?php

namespace bl\entity\atlas_providers\conveyor;

use bl\entity\atlas_providers\base\AbstractAtlasProvider;
use bl\entity\atlas_generators\ConveyorAtlasGenerator;
use bl\entity\sprite_generators\base\ImageProcessor;
use models\EntityType;

/**
 * Atlas provider for underground belt conveyors (In/Out)
 * Generates 5 atlases per state (normal, damaged, blueprint, normal_selected, damaged_selected)
 */
class UndergroundBeltAtlasProvider extends AbstractAtlasProvider
{
    public function getAtlasGenerators(EntityType $entityType): array
    {
        $animationGd = $this->getSourceSprite($entityType);

        // Underground belt needs same 5 atlases as regular conveyor
        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
        $generators = [];

        foreach ($states as $state) {
            $atlasName = "{$state}_atlas";
            $generators[$atlasName] = new ConveyorAtlasGenerator(
                $animationGd,
                $state,
                $entityType->orientation ?? 'right'
            );
        }

        return $generators;
    }

    public function getSourceSprite(EntityType $entityType)
    {
        // Underground belt uses animation.png if it exists
        $animationPath = $this->getSpritePath($entityType, 'animation.png');

        if (file_exists($animationPath)) {
            return imagecreatefrompng($animationPath);
        }

        // Otherwise, create animation from static sprite.png
        $spritePath = $this->getSpritePath($entityType, 'sprite.png');

        if (file_exists($spritePath)) {
            // Load static sprite
            $sprite = imagecreatefrompng($spritePath);
            $width = imagesx($sprite);
            $height = imagesy($sprite);

            // Create animation by duplicating sprite 8 times horizontally
            $animation = imagecreatetruecolor($width * 8, $height);
            imagealphablending($animation, false);
            imagesavealpha($animation, true);

            $transparent = imagecolorallocatealpha($animation, 0, 0, 0, 127);
            imagefill($animation, 0, 0, $transparent);

            // Copy sprite 8 times
            for ($i = 0; $i < 8; $i++) {
                imagecopy($animation, $sprite, $i * $width, 0, 0, 0, $width, $height);
            }

            imagedestroy($sprite);
            return $animation;
        }

        // If orientation variant, try parent
        if ($entityType->parent_entity_type_id) {
            $parent = EntityType::findOne($entityType->parent_entity_type_id);
            if ($parent) {
                // Try parent's animation first
                $parentAnimation = $this->getSpritePath($parent, 'animation.png');
                if (file_exists($parentAnimation)) {
                    $parentGd = imagecreatefrompng($parentAnimation);
                    $angle = $this->getRotationAngle($entityType->orientation);
                    $rotated = ImageProcessor::rotateImage($parentGd, $angle);
                    imagedestroy($parentGd);
                    return $rotated;
                }

                // Try parent's sprite and duplicate
                $parentSprite = $this->getSpritePath($parent, 'sprite.png');
                if (file_exists($parentSprite)) {
                    $sprite = imagecreatefrompng($parentSprite);

                    // Rotate sprite
                    $angle = $this->getRotationAngle($entityType->orientation);
                    $rotated = ImageProcessor::rotateImage($sprite, $angle);
                    imagedestroy($sprite);

                    // Duplicate to create animation
                    $width = imagesx($rotated);
                    $height = imagesy($rotated);

                    $animation = imagecreatetruecolor($width * 8, $height);
                    imagealphablending($animation, false);
                    imagesavealpha($animation, true);

                    $transparent = imagecolorallocatealpha($animation, 0, 0, 0, 127);
                    imagefill($animation, 0, 0, $transparent);

                    for ($i = 0; $i < 8; $i++) {
                        imagecopy($animation, $rotated, $i * $width, 0, 0, 0, $width, $height);
                    }

                    imagedestroy($rotated);
                    return $animation;
                }
            }
        }

        throw new \Exception("Sprite not found for entity_type_id={$entityType->entity_type_id} (folder={$entityType->type}/{$entityType->folder})");
    }
}
