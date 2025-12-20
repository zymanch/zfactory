<?php

namespace actions\game;

use actions\JsonAction;
use models\Landing;
use models\EntityType;
use models\Entity;
use models\Resource;
use models\Recipe;
use models\EntityTypeRecipe;
use services\BuildingRules;
use Yii;

/**
 * AJAX: Get game config with all reference data
 * Called once on game init, contains landing types, entity types, and URLs
 */
class Config extends JsonAction
{
    public function run()
    {
        // Get landing types
        $landingTypes = Landing::find()
            ->indexBy('landing_id')
            ->asArray()
            ->all();

        // Get entity types
        $entityTypes = EntityType::find()
            ->indexBy('entity_type_id')
            ->asArray()
            ->all();

        // Get all eye entity type IDs
        $eyeTypeIds = [];
        foreach ($entityTypes as $et) {
            if ($et['type'] === 'eye') {
                $eyeTypeIds[] = $et['entity_type_id'];
            }
        }

        // Get ALL eye entities (for fog of war) - not cached, need fresh data
        $eyeEntities = [];
        if (!empty($eyeTypeIds)) {
            $eyeEntities = Entity::find()
                ->select(['entity_id', 'entity_type_id', 'state', 'x', 'y'])
                ->where(['entity_type_id' => $eyeTypeIds])
                ->andWhere(['state' => 'built'])
                ->asArray()
                ->all();
        }

        // Get resources
        $resources = Resource::find()
            ->indexBy('resource_id')
            ->asArray()
            ->all();

        // Get recipes
        $recipes = Recipe::find()
            ->indexBy('recipe_id')
            ->asArray()
            ->all();

        // Get entity type recipes (which recipes are available for which entity types)
        $entityTypeRecipesRaw = EntityTypeRecipe::find()
            ->asArray()
            ->all();

        // Group by entity_type_id for easy lookup
        $entityTypeRecipes = [];
        foreach ($entityTypeRecipesRaw as $etr) {
            $typeId = (int) $etr['entity_type_id'];
            if (!isset($entityTypeRecipes[$typeId])) {
                $entityTypeRecipes[$typeId] = [];
            }
            $entityTypeRecipes[$typeId][] = (int) $etr['recipe_id'];
        }

        // Get user's build panel and camera position
        $buildPanel = array_fill(0, 10, null);
        $cameraX = 0;
        $cameraY = 0;
        $zoom = 1;
        if (!$this->isGuest()) {
            /** @var \models\User $user */
            $user = $this->getUser();
            $buildPanel = $user->getBuildPanelArray();
            $cameraX = (int)$user->camera_x;
            $cameraY = (int)$user->camera_y;
            $zoom = (float)$user->zoom;
        }

        return $this->success([
            'landing' => $landingTypes,
            'entityTypes' => $entityTypes,
            'eyeEntities' => $eyeEntities,
            'resources' => $resources,
            'recipes' => $recipes,
            'entityTypeRecipes' => $entityTypeRecipes,
            'buildPanel' => $buildPanel,
            'cameraPosition' => [
                'x' => $cameraX,
                'y' => $cameraY,
                'zoom' => $zoom,
            ],
            'config' => [
                'mapUrl' => \yii\helpers\Url::to(['map/tiles'], true),
                'entitiesUrl' => \yii\helpers\Url::to(['game/entities'], true),
                'createEntityUrl' => \yii\helpers\Url::to(['map/create-entity'], true),
                'entityResourcesUrl' => \yii\helpers\Url::to(['game/entity-resources'], true),
                'saveBuildPanelUrl' => \yii\helpers\Url::to(['user/save-build-panel'], true),
                'savePositionUrl' => \yii\helpers\Url::to(['user/save-position'], true),
                'tilesPath' => '/assets/tiles/',
                'tileWidth' => Yii::$app->params['tile_width'],
                'tileHeight' => Yii::$app->params['tile_height'],
                'assetVersion' => Yii::$app->params['asset_version'],
                'cameraSpeed' => 8,
            ],
            'buildingRules' => BuildingRules::getClientRules(),
        ]);
    }
}
