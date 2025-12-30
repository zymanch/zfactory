<?php

namespace actions\map;

use actions\JsonAction;
use models\Entity;
use models\ShipEntity;
use models\EntityTypeCost;
use Yii;

/**
 * AJAX: Delete entity
 * POST params: entity_id (can be numeric for island or 'ship_123' for ship)
 * Automatically detects ship vs island entity based on entity_id prefix
 */
class DeleteEntity extends JsonAction
{
    public function run()
    {
        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();

        // Validate required fields
        $entityIdRaw = $data['entity_id'] ?? '';

        if (!$entityIdRaw) {
            return $this->error('entity_id required');
        }

        // Determine if ship or island entity
        $isShipEntity = false;
        $entityId = 0;

        if (is_string($entityIdRaw) && strpos($entityIdRaw, 'ship_') === 0) {
            $isShipEntity = true;
            $entityId = (int) substr($entityIdRaw, 5); // Remove 'ship_' prefix
        } else {
            $entityId = (int) $entityIdRaw;
        }

        if (!$entityId) {
            return $this->error('Invalid entity_id');
        }

        // Find entity (ship or island)
        if ($isShipEntity) {
            $entity = ShipEntity::findOne($entityId);
            $modelName = 'ShipEntity';
        } else {
            $entity = Entity::findOne($entityId);
            $modelName = 'Entity';
        }

        if (!$entity) {
            return $this->error($modelName . ' not found');
        }

        // Begin transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Refund resources if deleting a blueprint
            if ($entity->state === 'blueprint') {
                EntityTypeCost::refundCost(Yii::$app->user->id, $entity->entity_type_id);
            }

            // Delete entity (cascades to delete related data: EntityResource for island, nothing for ship yet)
            if (!$entity->delete()) {
                throw new \Exception('Failed to delete ' . $modelName . ': ' . json_encode($entity->errors));
            }

            $transaction->commit();

            return $this->success([
                'entity_id' => $entityIdRaw,
                'message' => $modelName . ' deleted successfully'
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
