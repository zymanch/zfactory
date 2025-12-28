<?php

namespace actions\game;

use actions\JsonAction;
use models\Landing;
use models\LandingAdjacency;
use models\EntityType;
use models\Entity;
use models\Resource;
use models\Recipe;
use models\EntityTypeRecipe;
use models\EntityResource;
use models\EntityCrafting;
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
        $landingTypes = $this->castNumericFieldsIndexed(
            Landing::find()->indexBy('landing_id')->asArray()->all(),
            ['landing_id']
        );

        // Landing adjacencies not needed - using landing_id directly in atlas coordinates

        // Get entity types
        $entityTypes = $this->castNumericFieldsIndexed(
            EntityType::find()->indexBy('entity_type_id')->asArray()->all(),
            ['entity_type_id', 'power', 'max_durability', 'width', 'height']
        );

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
            $eyeEntities = $this->castNumericFieldsArray(
                Entity::find()
                    ->select(['entity_id', 'entity_type_id', 'state', 'x', 'y'])
                    ->where(['entity_type_id' => $eyeTypeIds])
                    ->andWhere(['state' => 'built'])
                    ->asArray()
                    ->all(),
                ['entity_id', 'entity_type_id', 'x', 'y']
            );
        }

        // Get resources
        $resources = $this->castNumericFieldsIndexed(
            Resource::find()->indexBy('resource_id')->asArray()->all(),
            ['resource_id', 'max_stack']
        );

        // Get recipes
        $recipes = $this->castNumericFieldsIndexed(
            Recipe::find()->indexBy('recipe_id')->asArray()->all(),
            ['recipe_id', 'ticks', 'input1_resource_id', 'input1_amount', 'input2_resource_id', 'input2_amount', 'input3_resource_id', 'input3_amount', 'output_resource_id', 'output_amount']
        );

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

        // Get all entity resources (for buildings, mining, storage)
        // Only records without transport state (position IS NULL)
        $entityResources = $this->castNumericFieldsArray(
            EntityResource::find()->where(['position' => null])->asArray()->all(),
            ['entity_id', 'resource_id', 'amount']
        );

        // Get all crafting states
        $craftingStates = $this->castNumericFieldsArray(
            EntityCrafting::find()->asArray()->all(),
            ['entity_id', 'recipe_id', 'ticks_remaining']
        );

        // Get all transport states (conveyors, manipulators)
        // Only records with transport state (position IS NOT NULL)
        $transportStates = $this->castNumericFieldsArray(
            EntityResource::find()->where(['not', ['position' => null]])->asArray()->all(),
            ['entity_id', 'resource_id', 'amount'],
            ['position', 'lateral_offset', 'arm_position']  // floats
        );

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
            'entityResources' => $entityResources,
            'craftingStates' => $craftingStates,
            'transportStates' => $transportStates,
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
                'deleteEntityUrl' => \yii\helpers\Url::to(['map/delete-entity'], true),
                'updateLandingUrl' => \yii\helpers\Url::to(['map/update-landing'], true),
                'saveBuildPanelUrl' => \yii\helpers\Url::to(['user/save-build-panel'], true),
                'savePositionUrl' => \yii\helpers\Url::to(['user/save-position'], true),
                'saveStateUrl' => \yii\helpers\Url::to(['game/save-state'], true),
                'finishConstructionUrl' => \yii\helpers\Url::to(['game/finish-construction'], true),
                'tilesPath' => '/assets/tiles/',
                'tileWidth' => Yii::$app->params['tile_width'],
                'tileHeight' => Yii::$app->params['tile_height'],
                'assetVersion' => Yii::$app->params['asset_version'],
                'autoSaveInterval' => Yii::$app->params['auto_save_interval'] ?? 60,
                'cameraSpeed' => 8,
            ],
            'buildingRules' => BuildingRules::getClientRules(),
        ]);
    }
}
