<?php

namespace services;

use models\Entity;
use models\EntityResource;
use models\Map;

/**
 * Service for calculating and applying shake damage to buildings
 */
class ShakeDamageService
{
    /**
     * Apply shake damage to all entities in region (runs every 180 ticks = 3 seconds)
     *
     * @param int $regionId Region ID
     * @param int $currentTick Current tick counter
     * @return void
     */
    public static function applyShakeDamage(int $regionId, int $currentTick): void
    {
        // Run every 60 ticks (1 second at 60fps) - TEMPORARY for testing
        $interval = 60;
        $remainder = $currentTick % $interval;

        if ($remainder !== 0) {
            return;
        }

        error_log("ShakeDamage: APPLYING DAMAGE at tick {$currentTick}");

        // Get all built entities in region
        $entities = Entity::find()
            ->where(['region_id' => $regionId, 'state' => 'built'])
            ->all();

        if (empty($entities)) {
            return;
        }

        // Get active stabilizers for protection check
        $stabilizers = self::getActiveStabilizers($regionId);

        foreach ($entities as $entity) {
            // Calculate total shake force for this entity
            $shakeForce = self::calculateEntityShake($entity, $regionId);

            if ($shakeForce <= 0) {
                continue;
            }

            // Check if protected by stabilizer
            if (self::isProtectedByStabilizer($entity, $stabilizers)) {
                continue;
            }

            // Apply damage: 0.5 damage per 3 sec at 1.0 intensity
            $damage = $shakeForce * 0.5;
            $oldDurability = $entity->durability;
            $entity->durability -= $damage;

            if ($entity->durability < 0) {
                $entity->durability = 0;
            }

            \Yii::info("Entity {$entity->entity_id}: durability {$oldDurability} -> {$entity->durability} (damage: {$damage}, shake: {$shakeForce})", 'shake');

            $entity->save(false);
        }
    }

    /**
     * Calculate total shake intensity for entity (accumulates from all tiles under it)
     *
     * @param Entity $entity Entity to check
     * @param int $regionId Region ID
     * @return float Total shake force (sum of all tiles)
     */
    private static function calculateEntityShake(Entity $entity, int $regionId): float
    {
        $entityType = $entity->entityType;
        $totalShake = 0.0;

        // Get all tiles under entity footprint
        for ($dy = 0; $dy < $entityType->height; $dy++) {
            for ($dx = 0; $dx < $entityType->width; $dx++) {
                $tileX = $entity->x + $dx;
                $tileY = $entity->y + $dy;

                $map = Map::findOne([
                    'region_id' => $regionId,
                    'x' => $tileX,
                    'y' => $tileY
                ]);

                if ($map && $map->shake_intensity) {
                    $totalShake += (float)$map->shake_intensity;
                }
            }
        }

        return $totalShake;
    }

    /**
     * Check if entity is protected by any active stabilizer
     *
     * @param Entity $entity Entity to check
     * @param array $stabilizers Active stabilizers in region
     * @return bool True if protected
     */
    private static function isProtectedByStabilizer(Entity $entity, array $stabilizers): bool
    {
        $entityCenterX = $entity->x + ($entity->entityType->width / 2);
        $entityCenterY = $entity->y + ($entity->entityType->height / 2);

        foreach ($stabilizers as $stabilizer) {
            $stabCenterX = $stabilizer->x + ($stabilizer->entityType->width / 2);
            $stabCenterY = $stabilizer->y + ($stabilizer->entityType->height / 2);

            // Calculate distance between centers
            $distance = sqrt(
                pow($entityCenterX - $stabCenterX, 2) +
                pow($entityCenterY - $stabCenterY, 2)
            );

            // Check if within stabilizer radius (power field contains radius)
            if ($distance <= $stabilizer->entityType->power) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all active stabilizers in region (powered and with resources)
     *
     * @param int $regionId Region ID
     * @return array Active stabilizer entities
     */
    private static function getActiveStabilizers(int $regionId): array
    {
        // Get all stabilizer buildings
        $stabilizers = Entity::find()
            ->joinWith('entityType')
            ->where([
                'entity.region_id' => $regionId,
                'entity.state' => 'built',
                'entity_type.subtype' => 'stabilizer'
            ])
            ->all();

        $active = [];

        foreach ($stabilizers as $stab) {
            // Medium Stabilizer (951): check electricity resource
            if ($stab->entity_type_id === 951) {
                $hasElectricity = EntityResource::find()
                    ->where([
                        'entity_id' => $stab->entity_id,
                        'resource_id' => 400,  // Electricity
                    ])
                    ->andWhere(['>', 'amount', 0])
                    ->exists();

                if ($hasElectricity) {
                    $active[] = $stab;
                }
            }
            // Small (950) and Large (952) Stabilizers: check Stability resource
            else {
                $hasStability = EntityResource::find()
                    ->where([
                        'entity_id' => $stab->entity_id,
                        'resource_id' => 450,  // Stability
                    ])
                    ->andWhere(['>', 'amount', 0])
                    ->exists();

                if ($hasStability) {
                    $active[] = $stab;
                }
            }
        }

        return $active;
    }
}
