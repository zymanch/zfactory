<?php

namespace actions\user;

use actions\JsonAction;
use models\User;
use Yii;

/**
 * AJAX: Save camera position and zoom
 * POST params: x, y, zoom
 */
class SavePosition extends JsonAction
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
        $x = isset($data['x']) ? (int)$data['x'] : null;
        $y = isset($data['y']) ? (int)$data['y'] : null;
        $zoom = isset($data['zoom']) ? (float)$data['zoom'] : null;

        if ($x === null || $y === null || $zoom === null) {
            return $this->error('Missing parameters');
        }

        // Clamp zoom to valid range
        $zoom = max(1.0, min(3.0, $zoom));

        /** @var User $user */
        $user = $this->getUser();
        $user->camera_x = $x;
        $user->camera_y = $y;
        $user->zoom = $zoom;

        if ($user->save(false, ['camera_x', 'camera_y', 'zoom'])) {
            return $this->success();
        }

        return $this->error('Failed to save');
    }
}
