<?php

namespace actions\game;

use actions\JsonAction;
use models\EntityResource;
use models\Resource;
use Yii;

/**
 * AJAX: Get resources for an entity
 */
class EntityResources extends JsonAction
{
    public function run()
    {
        $entityId = Yii::$app->request->get('entity_id');

        if (!$entityId) {
            return $this->error('Entity ID required');
        }

        $resources = EntityResource::find()
            ->alias('er')
            ->select(['er.resource_id', 'r.name', 'r.icon_url', 'r.type', 'er.amount'])
            ->innerJoin(['r' => Resource::tableName()], 'r.resource_id = er.resource_id')
            ->where(['er.entity_id' => $entityId])
            ->asArray()
            ->all();

        return $this->success(['resources' => $resources]);
    }
}
