<?php

namespace controllers\actions\game;

use controllers\actions\JsonAction;
use models\Entity;
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

        // Begin transaction
        $transaction = Yii::$app->db->beginTransaction();
        try {
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
