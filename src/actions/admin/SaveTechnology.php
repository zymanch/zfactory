<?php

namespace actions\admin;

use actions\JsonAction;
use models\Technology;
use models\TechnologyCost;
use models\TechnologyDependency;
use models\TechnologyUnlockRecipe;
use models\TechnologyUnlockEntityType;
use Yii;

/**
 * POST /admin/save-technology
 * Создание или обновление технологии
 */
class SaveTechnology extends JsonAction
{
    public function run()
    {
        $params = $this->getBodyParams();
        $id = (int)($params['technology_id'] ?? 0);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            // Создаём или находим технологию
            if ($id > 0) {
                $tech = Technology::findOne($id);
                if (!$tech) {
                    return $this->error('Technology not found');
                }
            } else {
                $tech = new Technology();
            }

            // Основные поля
            $tech->name = $params['name'] ?? '';
            $tech->description = $params['description'] ?? '';
            $tech->icon = $params['icon'] ?? '';
            $tech->tier = (int)($params['tier'] ?? 1);

            if (!$tech->save()) {
                return $this->error('Failed to save technology', $tech->errors);
            }

            $techId = $tech->technology_id;

            // Обновляем стоимость
            TechnologyCost::deleteAll(['technology_id' => $techId]);
            $costs = $params['costs'] ?? [];
            foreach ($costs as $cost) {
                $tc = new TechnologyCost();
                $tc->technology_id = $techId;
                $tc->resource_id = (int)$cost['resource_id'];
                $tc->quantity = (int)$cost['quantity'];
                $tc->save();
            }

            // Обновляем зависимости
            TechnologyDependency::deleteAll(['technology_id' => $techId]);
            $requires = $params['requires'] ?? [];
            foreach ($requires as $reqId) {
                $td = new TechnologyDependency();
                $td->technology_id = $techId;
                $td->required_technology_id = (int)$reqId;
                $td->save();
            }

            // Обновляем разблокируемые рецепты
            TechnologyUnlockRecipe::deleteAll(['technology_id' => $techId]);
            $recipes = $params['unlocks_recipes'] ?? [];
            foreach ($recipes as $recipeId) {
                $tur = new TechnologyUnlockRecipe();
                $tur->technology_id = $techId;
                $tur->recipe_id = (int)$recipeId;
                $tur->save();
            }

            // Обновляем разблокируемые entity types
            TechnologyUnlockEntityType::deleteAll(['technology_id' => $techId]);
            $entities = $params['unlocks_entities'] ?? [];
            foreach ($entities as $entityId) {
                $tue = new TechnologyUnlockEntityType();
                $tue->technology_id = $techId;
                $tue->entity_type_id = (int)$entityId;
                $tue->save();
            }

            $transaction->commit();

            return $this->success([
                'technology_id' => $techId,
                'message' => $id > 0 ? 'Technology updated' : 'Technology created'
            ]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
