<?php

namespace actions\research;

use actions\JsonAction;
use models\Technology;
use models\UserTechnology;
use models\UserResource;
use Yii;

/**
 * POST /research/unlock
 * Изучить технологию
 */
class Unlock extends JsonAction
{
    public function run()
    {
        $userId = $this->getUser()->user_id;
        $params = $this->getBodyParams();
        $technologyId = (int)($params['technology_id'] ?? 0);

        // Найти технологию
        $technology = Technology::findOne($technologyId);
        if (!$technology) {
            return $this->error('Technology not found');
        }

        // Проверить, не изучена ли уже
        if (UserTechnology::isResearched($userId, $technologyId)) {
            return $this->error('Technology already researched');
        }

        // Проверить зависимости
        $researchedIds = UserTechnology::getResearchedIds($userId);
        $status = $technology->getStatus($researchedIds);
        if ($status === 'locked') {
            return $this->error('Prerequisites not met');
        }

        // Проверить ресурсы
        $costs = $technology->technologyCosts;
        foreach ($costs as $cost) {
            if (!UserResource::hasEnough($userId, $cost->resource_id, $cost->quantity)) {
                return $this->error('Not enough resources', [
                    'resource_id' => $cost->resource_id,
                    'required' => $cost->quantity,
                ]);
            }
        }

        // Начать транзакцию
        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Списать ресурсы
            foreach ($costs as $cost) {
                UserResource::deductResource($userId, $cost->resource_id, $cost->quantity);
            }

            // Записать исследование
            if (!UserTechnology::research($userId, $technologyId)) {
                throw new \Exception('Failed to save research');
            }

            $transaction->commit();

            return $this->success([
                'unlocked' => [
                    'recipes' => $technology->getUnlockedRecipeIds(),
                    'entity_types' => $technology->getUnlockedEntityTypeIds(),
                ],
                'resourcesSpent' => array_map(function ($cost) {
                    return [
                        'resource_id' => $cost->resource_id,
                        'quantity' => $cost->quantity,
                    ];
                }, $costs),
            ]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
