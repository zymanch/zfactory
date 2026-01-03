<?php

namespace actions\admin;

use actions\JsonAction;
use models\Technology;

/**
 * GET /admin/technologies
 * Список технологий для админки
 */
class Technologies extends JsonAction
{
    public function run()
    {
        $technologies = Technology::find()
            ->with(['technologyCosts', 'technologyCosts.resource'])
            ->orderBy(['tier' => SORT_ASC, 'technology_id' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($technologies as $tech) {
            $costs = [];
            foreach ($tech->technologyCosts as $cost) {
                $costs[] = [
                    'resource_id' => $cost->resource_id,
                    'name' => $cost->resource->name ?? 'Unknown',
                    'quantity' => $cost->quantity,
                ];
            }

            $result[] = [
                'technology_id' => $tech->technology_id,
                'name' => $tech->name,
                'description' => $tech->description,
                'icon' => $tech->icon,
                'tier' => $tech->tier,
                'costs' => $costs,
                'requires' => $tech->getRequiredTechnologyIds(),
                'unlocks_recipes' => $tech->getUnlockedRecipeIds(),
                'unlocks_entities' => $tech->getUnlockedEntityTypeIds(),
            ];
        }

        return $this->success(['technologies' => $result]);
    }
}
