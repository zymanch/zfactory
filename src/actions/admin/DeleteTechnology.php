<?php

namespace actions\admin;

use actions\JsonAction;
use models\Technology;

/**
 * POST /admin/delete-technology
 * Удаление технологии
 */
class DeleteTechnology extends JsonAction
{
    public function run()
    {
        $params = $this->getBodyParams();
        $id = (int)($params['technology_id'] ?? 0);

        if ($id <= 0) {
            return $this->error('Invalid technology_id');
        }

        $tech = Technology::findOne($id);
        if (!$tech) {
            return $this->error('Technology not found');
        }

        // Связанные записи удалятся автоматически через ON DELETE CASCADE
        if ($tech->delete()) {
            return $this->success(['message' => 'Technology deleted']);
        }

        return $this->error('Failed to delete technology');
    }
}
