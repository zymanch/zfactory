<?php

namespace actions\map;

use actions\JsonAction;
use models\Entity;
use Yii;

/**
 * AJAX: Delete entity
 * POST params: entity_id
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
        $entityId = (int) ($data['entity_id'] ?? 0);

        if (!$entityId) {
            return $this->error('entity_id required');
        }

        // Find entity
        $entity = Entity::findOne($entityId);
        if (!$entity) {
            return $this->error('Entity not found');
        }

        // Begin transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Delete entity (cascades to delete related data: EntityResource, etc.)
            if (!$entity->delete()) {
                throw new \Exception('Failed to delete entity: ' . json_encode($entity->errors));
            }

            $transaction->commit();

            return $this->success([
                'entity_id' => $entityId,
                'message' => 'Entity deleted successfully'
            ]);

        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
