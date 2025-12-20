<?php

namespace actions\map;

use actions\JsonAction;
use models\Entity;
use models\EntityType;
use models\EntityResource;
use services\BuildingRules;
use Yii;

/**
 * AJAX: Create new entity (building placement)
 * POST params: entity_type_id, x, y (tile coordinates), state, target_entity_id (optional)
 */
class CreateEntity extends JsonAction
{
    public function run()
    {
        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();

        // Validate required fields
        $entityTypeId = (int) ($data['entity_type_id'] ?? 0);
        $tileX = (int) ($data['x'] ?? 0);
        $tileY = (int) ($data['y'] ?? 0);
        $state = $data['state'] ?? 'blueprint';
        $targetEntityId = isset($data['target_entity_id']) ? (int) $data['target_entity_id'] : null;

        if (!$entityTypeId) {
            return $this->error('entity_type_id required');
        }

        // Check entity type exists
        $entityType = EntityType::findOne($entityTypeId);
        if (!$entityType) {
            return $this->error('Invalid entity_type_id');
        }

        // Check building rules using behavior system (tile coordinates)
        // This checks: fog of war, landing buildability, entity collision, resource target
        $ruleCheck = BuildingRules::canPlace($entityTypeId, $tileX, $tileY);
        $targetEntity = $ruleCheck['targetEntity'];

        // If building placement is not allowed
        if (!$ruleCheck['allowed']) {
            return $this->error($ruleCheck['error'] ?? 'Cannot place here');
        }

        // Validate target_entity_id matches the rule check (for mining entities)
        if ($targetEntityId && $targetEntity && $targetEntity->entity_id != $targetEntityId) {
            return $this->error('Target entity mismatch');
        }

        // Begin transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Create new entity with tile coordinates
            $entity = new Entity();
            $entity->entity_type_id = $entityTypeId;
            $entity->x = $tileX;
            $entity->y = $tileY;
            $entity->state = $state;
            $entity->durability = $state === 'built' ? $entityType->max_durability : 0;

            if (!$entity->save()) {
                throw new \Exception('Failed to save entity: ' . json_encode($entity->errors));
            }

            $targetRemoved = false;

            // Transfer resources from target entity to new entity
            if ($targetEntity) {
                // Get resources from target entity
                $resources = EntityResource::findAll(['entity_id' => $targetEntity->entity_id]);

                // Transfer each resource to new entity
                foreach ($resources as $resource) {
                    $newResource = new EntityResource();
                    $newResource->entity_id = $entity->entity_id;
                    $newResource->resource_id = $resource->resource_id;
                    $newResource->amount = $resource->amount;
                    if (!$newResource->save()) {
                        throw new \Exception('Failed to transfer resources');
                    }
                }

                // Delete target entity (cascades to delete its resources)
                if (!$targetEntity->delete()) {
                    throw new \Exception('Failed to remove target entity');
                }

                $targetRemoved = true;
            }

            $transaction->commit();

            return $this->success([
                'entity' => [
                    'entity_id' => $entity->entity_id,
                    'entity_type_id' => $entity->entity_type_id,
                    'x' => $entity->x,
                    'y' => $entity->y,
                    'state' => $entity->state,
                    'durability' => $entity->durability,
                ],
                'targetRemoved' => $targetRemoved,
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
