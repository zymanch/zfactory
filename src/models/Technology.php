<?php

namespace models;

use models\base;

class Technology extends base\BaseTechnology
{
    /**
     * Получить статус технологии для пользователя
     *
     * @param array $researchedIds ID уже изученных технологий
     * @return string locked|available|researched
     */
    public function getStatus(array $researchedIds): string
    {
        // Уже изучена
        if (in_array($this->technology_id, $researchedIds)) {
            return 'researched';
        }

        // Проверяем зависимости
        $requiredIds = $this->getRequiredTechnologyIds();
        foreach ($requiredIds as $reqId) {
            if (!in_array($reqId, $researchedIds)) {
                return 'locked';
            }
        }

        return 'available';
    }

    /**
     * Получить ID требуемых технологий
     *
     * @return array
     */
    public function getRequiredTechnologyIds(): array
    {
        return TechnologyDependency::find()
            ->select('required_technology_id')
            ->where(['technology_id' => $this->technology_id])
            ->column();
    }

    /**
     * Получить ID разблокируемых рецептов
     *
     * @return array
     */
    public function getUnlockedRecipeIds(): array
    {
        return TechnologyUnlockRecipe::find()
            ->select('recipe_id')
            ->where(['technology_id' => $this->technology_id])
            ->column();
    }

    /**
     * Получить ID разблокируемых типов зданий
     *
     * @return array
     */
    public function getUnlockedEntityTypeIds(): array
    {
        return TechnologyUnlockEntityType::find()
            ->select('entity_type_id')
            ->where(['technology_id' => $this->technology_id])
            ->column();
    }

    /**
     * Форматировать стоимость для API
     *
     * @return array
     */
    public function formatCosts(): array
    {
        $costs = [];
        foreach ($this->technologyCosts as $cost) {
            $resource = $cost->resource;
            $costs[] = [
                'resource_id' => $cost->resource_id,
                'name' => $resource->name,
                'icon' => $resource->icon_url,
                'quantity' => $cost->quantity,
            ];
        }
        return $costs;
    }

    /**
     * Форматировать для API
     *
     * @param array $researchedIds ID изученных технологий
     * @return array
     */
    public function toApi(array $researchedIds): array
    {
        return [
            'id' => $this->technology_id,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'tier' => $this->tier,
            'cost' => $this->formatCosts(),
            'requires' => $this->getRequiredTechnologyIds(),
            'unlocks' => [
                'recipes' => $this->getUnlockedRecipeIds(),
                'entity_types' => $this->getUnlockedEntityTypeIds(),
            ],
            'status' => $this->getStatus($researchedIds),
        ];
    }

    /**
     * Получить все технологии с подгруженными зависимостями
     *
     * @return Technology[]
     */
    public static function getAllWithRelations(): array
    {
        return self::find()
            ->with(['technologyCosts', 'technologyCosts.resource'])
            ->orderBy(['tier' => SORT_ASC, 'technology_id' => SORT_ASC])
            ->all();
    }
}