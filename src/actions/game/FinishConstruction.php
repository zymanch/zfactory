<?php

namespace actions\game;

use actions\JsonAction;
use models\Entity;
use models\ShipEntity;
use models\ShipLanding;
use models\EntityType;
use Yii;

/**
 * Finish construction - convert blueprint to built state
 * POST params: entity_id (can be numeric or 'ship_123')
 * For ship floor entities: converts to ship_landing when finished
 */
class FinishConstruction extends JsonAction
{
    public function run()
    {
        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();
        $entityIdRaw = $data['entity_id'] ?? '';

        if (!$entityIdRaw) {
            return $this->error('entity_id required');
        }

        // Determine if ship or island entity
        $isShipEntity = false;
        $entityId = 0;

        if (is_string($entityIdRaw) && strpos($entityIdRaw, 'ship_') === 0) {
            $isShipEntity = true;
            $entityId = (int) substr($entityIdRaw, 5);
        } else {
            $entityId = (int) $entityIdRaw;
        }

        if (!$entityId) {
            return $this->error('Invalid entity_id');
        }

        // Find entity (ship or island)
        if ($isShipEntity) {
            $entity = ShipEntity::findOne($entityId);
        } else {
            $entity = Entity::findOne($entityId);
        }

        if (!$entity) {
            return $this->error('Entity not found');
        }

        if ($entity->state !== 'blueprint') {
            return $this->error('Entity is not under construction');
        }

        $entityType = EntityType::findOne($entity->entity_type_id);
        if (!$entityType) {
            return $this->error('Entity type not found');
        }

        // Begin transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Check if entity should convert to landing
            $convertsToLandingId = $entityType->converts_to_landing_id;
            $converted = false;

            if ($convertsToLandingId && $isShipEntity) {
                // Convert ship entity to ship landing
                // Check if landing already exists (shouldn't happen, but safety check)
                $existingLanding = ShipLanding::findOne([
                    'user_id' => $entity->user_id,
                    'x' => $entity->x,
                    'y' => $entity->y,
                ]);

                if (!$existingLanding) {
                    $shipLanding = new ShipLanding();
                    $shipLanding->user_id = $entity->user_id;
                    $shipLanding->landing_id = $convertsToLandingId;
                    $shipLanding->x = $entity->x;
                    $shipLanding->y = $entity->y;
                    $shipLanding->variation = rand(0, 4); // Random variation

                    if (!$shipLanding->save()) {
                        throw new \Exception('Failed to create ship landing: ' . json_encode($shipLanding->errors));
                    }

                    $responseEntityId = 'landing_' . $shipLanding->ship_landing_id;
                } else {
                    // Landing already exists, use it
                    $responseEntityId = 'landing_' . $existingLanding->ship_landing_id;
                }

                // Delete the ship entity
                if (!$entity->delete()) {
                    throw new \Exception('Failed to delete ship entity');
                }

                $converted = true;
            } else {
                // Normal construction finish (no conversion)
                $entity->state = 'built';
                $entity->durability = $entityType->max_durability;

                if ($isShipEntity) {
                    // ShipEntity doesn't have construction_progress field
                } else {
                    $entity->construction_progress = 100;
                }

                if (!$entity->save()) {
                    throw new \Exception('Failed to save entity: ' . json_encode($entity->errors));
                }

                $responseEntityId = $entityIdRaw;
            }

            $transaction->commit();

            return $this->success([
                'entity_id' => $responseEntityId,
                'state' => $converted ? 'converted' : 'built',
                'construction_progress' => $converted ? null : ($isShipEntity ? null : $entity->construction_progress),
                'durability' => $converted ? null : $entity->durability,
                'converted' => $converted,
                'converted_to_landing_id' => $converted ? $convertsToLandingId : null,
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
