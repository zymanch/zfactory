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
use models\EntityTypeCost;
use models\UserResource;
use models\DepositType;
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

        // Get deposit types
        $depositTypes = $this->castNumericFieldsIndexed(
            DepositType::find()->indexBy('deposit_type_id')->asArray()->all(),
            ['deposit_type_id', 'resource_id', 'resource_amount', 'width', 'height']
        );

        // Get all eye entity type IDs
        $eyeTypeIds = [];
        foreach ($entityTypes as $et) {
            if ($et['type'] === 'eye') {
                $eyeTypeIds[] = $et['entity_type_id'];
            }
        }

        // Get current region ID
        $currentRegionId = 1; // Default
        if (!$this->isGuest()) {
            $currentRegionId = (int)$this->getUser()->current_region_id;
        }

        // Get region data (including ship_attach coordinates)
        $region = \models\Region::findOne($currentRegionId);
        $regionData = null;
        if ($region) {
            $regionData = [
                'region_id' => (int)$region->region_id,
                'name' => $region->name,
                'ship_attach_x' => (int)$region->ship_attach_x,
                'ship_attach_y' => (int)$region->ship_attach_y,
            ];
        }

        // Get ALL eye entities (for fog of war) - not cached, need fresh data
        // Filter by current region
        $eyeEntities = [];
        if (!empty($eyeTypeIds)) {
            $eyeEntities = $this->castNumericFieldsArray(
                Entity::find()
                    ->select(['entity_id', 'entity_type_id', 'state', 'x', 'y'])
                    ->where(['entity_type_id' => $eyeTypeIds])
                    ->andWhere(['state' => 'built'])
                    ->andWhere(['region_id' => $currentRegionId])
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

        // Get all deposits (trees, rocks, ores) - filter by current region
        $deposits = $this->castNumericFieldsArray(
            \models\Deposit::find()->where(['region_id' => $currentRegionId])->asArray()->all(),
            ['deposit_id', 'deposit_type_id', 'x', 'y', 'resource_amount']
        );

        // Get entity type costs
        $entityTypeCostsRaw = EntityTypeCost::find()->asArray()->all();
        $entityTypeCosts = [];
        foreach ($entityTypeCostsRaw as $cost) {
            $typeId = (int)$cost['entity_type_id'];
            if (!isset($entityTypeCosts[$typeId])) {
                $entityTypeCosts[$typeId] = [];
            }
            $entityTypeCosts[$typeId][(int)$cost['resource_id']] = (int)$cost['quantity'];
        }

        // Get user resources
        $userResources = [];
        if (!$this->isGuest()) {
            $userResourcesRaw = UserResource::find()
                ->where(['user_id' => $this->getUser()->user_id])
                ->asArray()
                ->all();

            foreach ($userResourcesRaw as $ur) {
                $userResources[(int)$ur['resource_id']] = (int)$ur['quantity'];
            }
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
            'depositTypes' => $depositTypes,
            'eyeEntities' => $eyeEntities,
            'deposits' => $deposits,
            'resources' => $resources,
            'recipes' => $recipes,
            'entityTypeRecipes' => $entityTypeRecipes,
            'entityTypeCosts' => $entityTypeCosts,
            'userResources' => $userResources,
            'entityResources' => $entityResources,
            'craftingStates' => $craftingStates,
            'transportStates' => $transportStates,
            'region' => $regionData,
            'buildPanel' => $buildPanel,
            'cameraPosition' => [
                'x' => $cameraX,
                'y' => $cameraY,
                'zoom' => $zoom,
            ],
            'config' => [
                'mapUrl' => \yii\helpers\Url::to(['map/tiles'], true),
                'entitiesUrl' => \yii\helpers\Url::to(['game/entities'], true),
                'depositsUrl' => \yii\helpers\Url::to(['game/deposits'], true),
                'createEntityUrl' => \yii\helpers\Url::to(['map/create-entity'], true),
                'deleteEntityUrl' => \yii\helpers\Url::to(['map/delete-entity'], true),
                'updateLandingUrl' => \yii\helpers\Url::to(['map/update-landing'], true),
                'saveBuildPanelUrl' => \yii\helpers\Url::to(['user/save-build-panel'], true),
                'savePositionUrl' => \yii\helpers\Url::to(['user/save-position'], true),
                'saveStateUrl' => \yii\helpers\Url::to(['game/save-state'], true),
                'finishConstructionUrl' => \yii\helpers\Url::to(['game/finish-construction'], true),
                'addUserResourceUrl' => \yii\helpers\Url::to(['game/add-user-resource'], true),
                'regionsMapUrl' => \yii\helpers\Url::to(['regions/index'], true),
                'tilesPath' => '/assets/tiles/',
                'currentRegionId' => $currentRegionId,
                'tileWidth' => Yii::$app->params['tile_width'],
                'tileHeight' => Yii::$app->params['tile_height'],
                'assetVersion' => Yii::$app->params['asset_version'],
                'autoSaveInterval' => Yii::$app->params['auto_save_interval'] ?? 60,
                'debug' => Yii::$app->params['debug'] ?? false,

                // Landing IDs constants
                'landingSkyId' => Yii::$app->params['landing_sky_id'],
                'landingBridgeId' => Yii::$app->params['landing_bridge_id'],
                'landingIslandEdgeId' => Yii::$app->params['landing_island_edge_id'],
                'landingShipEdgeId' => Yii::$app->params['landing_ship_edge_id'],
                'cameraSpeed' => 8,
            ],
            'buildingRules' => BuildingRules::getClientRules(),
        ]);
    }
}
