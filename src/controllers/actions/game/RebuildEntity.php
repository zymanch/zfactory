<?php

namespace controllers\actions\game;

use controllers\actions\JsonAction;
use models\Entity;
use models\EntityTypeCost;
use models\UserResource;
use Yii;

/**
 * Rebuild broken building (durability=0) - converts to blueprint state
 * POST params: entity_id
 */
class RebuildEntity extends JsonAction
{
    public function run()
    {
        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();
        $entityId = $data['entity_id'] ?? 0;

        if (!$entityId) {
            return $this->error('entity_id required');
        }

        $entity = Entity::findOne($entityId);

        if (!$entity) {
            return $this->error('Entity not found');
        }

        // Only broken buildings (durability=0) can be rebuilt
        if ($entity->durability > 0) {
            return $this->error('Entity is not broken');
        }

        // Get building costs
        $costs = EntityTypeCost::find()
            ->where(['entity_type_id' => $entity->entity_type_id])
            ->asArray()
            ->all();

        $userId = $this->getUser()->user_id;

        // Check if user has enough resources
        foreach ($costs as $cost) {
            $resourceId = (int)$cost['resource_id'];
            $requiredAmount = (int)$cost['quantity'];

            $userResource = UserResource::findOne([
                'user_id' => $userId,
                'resource_id' => $resourceId
            ]);

            $available = $userResource ? (int)$userResource->quantity : 0;

            if ($available < $requiredAmount) {
                return $this->error("Not enough resources (resource_id: {$resourceId}, need: {$requiredAmount}, have: {$available})");
            }
        }

        // Begin transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Deduct resources from user
            foreach ($costs as $cost) {
                $resourceId = (int)$cost['resource_id'];
                $requiredAmount = (int)$cost['quantity'];

                $userResource = UserResource::findOne([
                    'user_id' => $userId,
                    'resource_id' => $resourceId
                ]);

                $userResource->quantity -= $requiredAmount;

                if ($userResource->quantity < 0) {
                    throw new \Exception("Resource calculation error for resource_id: {$resourceId}");
                }

                if ($userResource->quantity == 0) {
                    $userResource->delete();
                } else {
                    if (!$userResource->save()) {
                        throw new \Exception('Failed to save user resource: ' . json_encode($userResource->errors));
                    }
                }
            }

            // Convert to blueprint state for reconstruction
            $entity->state = 'blueprint';
            $entity->construction_progress = 0;

            if (!$entity->save()) {
                throw new \Exception('Failed to save entity: ' . json_encode($entity->errors));
            }

            $transaction->commit();

            return $this->success([
                'entity_id' => $entityId,
                'state' => 'blueprint',
                'construction_progress' => 0,
                'durability' => 0,
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
