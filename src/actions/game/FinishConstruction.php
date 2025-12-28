<?php

namespace actions\game;

use actions\JsonAction;
use models\Entity;
use models\EntityType;
use Yii;

/**
 * Finish construction - convert blueprint to built state
 * POST params: entity_id
 */
class FinishConstruction extends JsonAction
{
    public function run()
    {
        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();
        $entityId = (int) ($data['entity_id'] ?? 0);

        if (!$entityId) {
            return $this->error('entity_id required');
        }

        $entity = Entity::findOne($entityId);
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

        // Finish construction
        $entity->state = 'built';
        $entity->construction_progress = 100;
        $entity->durability = $entityType->max_durability;

        if (!$entity->save()) {
            return $this->error('Failed to save entity: ' . json_encode($entity->errors));
        }

        return $this->success([
            'entity_id' => $entity->entity_id,
            'state' => $entity->state,
            'construction_progress' => $entity->construction_progress,
            'durability' => $entity->durability,
        ]);
    }
}
