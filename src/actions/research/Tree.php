<?php

namespace actions\research;

use actions\JsonAction;
use models\Technology;
use models\UserTechnology;

/**
 * GET /research/tree
 * Получить дерево технологий с состоянием для текущего пользователя
 */
class Tree extends JsonAction
{
    public function run()
    {
        $userId = $this->getUser()->user_id;
        $researchedIds = UserTechnology::getResearchedIds($userId);

        $technologies = Technology::getAllWithRelations();

        $result = [];
        foreach ($technologies as $tech) {
            $result[] = $tech->toApi($researchedIds);
        }

        return $this->success(['technologies' => $result]);
    }
}
