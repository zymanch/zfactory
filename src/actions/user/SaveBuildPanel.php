<?php

namespace actions\user;

use actions\JsonAction;
use models\User;
use Yii;

/**
 * AJAX: Save build panel state
 * POST params: slots (array of 10 entity_type_ids or nulls)
 */
class SaveBuildPanel extends JsonAction
{
    public function run()
    {
        if ($this->isGuest()) {
            return $this->error('Not authenticated');
        }

        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();
        $slots = $data['slots'] ?? [];

        if (!is_array($slots)) {
            return $this->error('Invalid slots data');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setBuildPanelArray($slots);

        if ($user->save(false, ['build_panel'])) {
            return $this->success();
        }

        return $this->error('Failed to save');
    }
}
