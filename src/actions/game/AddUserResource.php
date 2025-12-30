<?php

namespace actions\game;

use actions\JsonAction;
use models\UserResource;
use Yii;

/**
 * AddUserResource - adds resources to user's global inventory
 * Used when HQ receives resources from manipulators
 */
class AddUserResource extends JsonAction
{
    public function run()
    {
        if ($this->isGuest()) {
            return $this->error('Not authorized');
        }

        $userId = $this->getUser()->user_id;
        $resourceId = (int)Yii::$app->request->post('resource_id');
        $amount = (int)Yii::$app->request->post('amount', 1);

        if (!$resourceId || $amount <= 0) {
            return $this->error('Invalid parameters');
        }

        // Add resource to user inventory
        UserResource::addResource($userId, $resourceId, $amount);

        // Get updated user resources
        $userResourcesRaw = UserResource::find()
            ->where(['user_id' => $userId])
            ->asArray()
            ->all();

        $userResources = [];
        foreach ($userResourcesRaw as $ur) {
            $userResources[(int)$ur['resource_id']] = (int)$ur['quantity'];
        }

        return $this->success([
            'userResources' => $userResources
        ]);
    }
}
