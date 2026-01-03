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
    protected function getCurrentRegionId()
    {
        if (!$this->isGuest()) {
            return (int)$this->getUser()->current_region_id;
        }
        return 1;
    }

    protected function getLandingTypes()
    {
        return $this->castNumericFieldsIndexed(
            Landing::find()->indexBy('landing_id')->asArray()->all(),
            ['landing_id']
        );
    }

    protected function getEntityTypes()
    {
        return $this->castNumericFieldsIndexed(
            EntityType::find()->indexBy('entity_type_id')->asArray()->all(),
            ['entity_type_id', 'power', 'max_durability', 'width', 'height']
        );
    }

    protected function getDepositTypes()
    {
        return $this->castNumericFieldsIndexed(
            DepositType::find()->indexBy('deposit_type_id')->asArray()->all(),
            ['deposit_type_id', 'resource_id', 'resource_amount', 'width', 'height']
        );
    }

    protected function getEyeEntities($entityTypes, $currentRegionId)
    {
        $eyeTypeIds = [];
        foreach ($entityTypes as $et) {
            if ($et['type'] === 'eye') {
                $eyeTypeIds[] = $et['entity_type_id'];
            }
        }

        if (empty($eyeTypeIds)) {
            return [];
        }

        return $this->castNumericFieldsArray(
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

    protected function getRegion($currentRegionId)
    {
        $region = \models\Region::findOne($currentRegionId);
        if (!$region) {
            return null;
        }

        return [
            'region_id' => (int)$region->region_id,
            'name' => $region->name,
            'ship_attach_x' => (int)$region->ship_attach_x,
            'ship_attach_y' => (int)$region->ship_attach_y,
        ];
    }

    protected function getResources()
    {
        return $this->castNumericFieldsIndexed(
            Resource::find()->indexBy('resource_id')->asArray()->all(),
            ['resource_id', 'max_stack']
        );
    }

    protected function getRecipes()
    {
        return $this->castNumericFieldsIndexed(
            Recipe::find()->indexBy('recipe_id')->asArray()->all(),
            ['recipe_id', 'ticks', 'input1_resource_id', 'input1_amount', 'input2_resource_id', 'input2_amount', 'input3_resource_id', 'input3_amount', 'output_resource_id', 'output_amount']
        );
    }

    protected function getEntityTypeRecipes()
    {
        $entityTypeRecipesRaw = EntityTypeRecipe::find()->asArray()->all();

        $entityTypeRecipes = [];
        foreach ($entityTypeRecipesRaw as $etr) {
            $typeId = (int) $etr['entity_type_id'];
            if (!isset($entityTypeRecipes[$typeId])) {
                $entityTypeRecipes[$typeId] = [];
            }
            $entityTypeRecipes[$typeId][] = (int) $etr['recipe_id'];
        }

        return $entityTypeRecipes;
    }

    protected function getEntityResources($currentRegionId)
    {
        return $this->castNumericFieldsArray(
            EntityResource::find()
                ->alias('er')
                ->innerJoin('entity e', 'e.entity_id = er.entity_id')
                ->where(['er.position' => null])
                ->andWhere(['e.region_id' => $currentRegionId])
                ->select(['er.entity_id', 'er.resource_id', 'er.amount'])
                ->asArray()
                ->all(),
            ['entity_id', 'resource_id', 'amount']
        );
    }

    protected function getCraftingStates($currentRegionId)
    {
        return $this->castNumericFieldsArray(
            EntityCrafting::find()
                ->alias('ec')
                ->innerJoin('entity e', 'e.entity_id = ec.entity_id')
                ->where(['e.region_id' => $currentRegionId])
                ->select(['ec.entity_id', 'ec.recipe_id', 'ec.ticks_remaining'])
                ->asArray()
                ->all(),
            ['entity_id', 'recipe_id', 'ticks_remaining']
        );
    }

    protected function getTransportStates($currentRegionId)
    {
        return $this->castNumericFieldsArray(
            EntityResource::find()
                ->alias('er')
                ->innerJoin('entity e', 'e.entity_id = er.entity_id')
                ->where(['not', ['er.position' => null]])
                ->andWhere(['e.region_id' => $currentRegionId])
                ->select(['er.entity_id', 'er.resource_id', 'er.amount', 'er.position', 'er.lateral_offset', 'er.arm_position'])
                ->asArray()
                ->all(),
            ['entity_id', 'resource_id', 'amount'],
            ['position', 'lateral_offset', 'arm_position']
        );
    }

    protected function getDeposits($currentRegionId)
    {
        return $this->castNumericFieldsArray(
            \models\Deposit::find()->where(['region_id' => $currentRegionId])->asArray()->all(),
            ['deposit_id', 'deposit_type_id', 'x', 'y', 'resource_amount']
        );
    }

    protected function getEntityTypeCosts()
    {
        $entityTypeCostsRaw = EntityTypeCost::find()->asArray()->all();
        $entityTypeCosts = [];
        foreach ($entityTypeCostsRaw as $cost) {
            $typeId = (int)$cost['entity_type_id'];
            if (!isset($entityTypeCosts[$typeId])) {
                $entityTypeCosts[$typeId] = [];
            }
            $entityTypeCosts[$typeId][(int)$cost['resource_id']] = (int)$cost['quantity'];
        }

        return $entityTypeCosts;
    }

    protected function getUserResources()
    {
        if ($this->isGuest()) {
            return [];
        }

        $userResourcesRaw = UserResource::find()
            ->where(['user_id' => $this->getUser()->user_id])
            ->asArray()
            ->all();

        $userResources = [];
        foreach ($userResourcesRaw as $ur) {
            $userResources[(int)$ur['resource_id']] = (int)$ur['quantity'];
        }

        return $userResources;
    }

    protected function getBuildPanel()
    {
        if ($this->isGuest()) {
            return array_fill(0, 10, null);
        }

        return $this->getUser()->getBuildPanelArray();
    }

    protected function getCameraPosition()
    {
        if ($this->isGuest()) {
            return ['x' => 0, 'y' => 0, 'zoom' => 1];
        }

        $user = $this->getUser();
        return [
            'x' => (int)$user->camera_x,
            'y' => (int)$user->camera_y,
            'zoom' => (float)$user->zoom,
        ];
    }

    protected function getConfig($currentRegionId)
    {
        return [
            'mapUrl' => \yii\helpers\Url::to(['map/tiles'], true),
            'entitiesUrl' => \yii\helpers\Url::to(['game/entities'], true),
            'depositsUrl' => \yii\helpers\Url::to(['game/deposits'], true),
            'createEntityUrl' => \yii\helpers\Url::to(['map/create-entity'], true),
            'deleteEntityUrl' => \yii\helpers\Url::to(['map/delete-entity'], true),
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
            'landingSkyId' => Yii::$app->params['landing_sky_id'],
            'landingBridgeId' => Yii::$app->params['landing_bridge_id'],
            'landingIslandEdgeId' => Yii::$app->params['landing_island_edge_id'],
            'landingShipEdgeId' => Yii::$app->params['landing_ship_edge_id'],
            'cameraSpeed' => 8,
        ];
    }

    protected function getBuildingRules()
    {
        return BuildingRules::getClientRules();
    }

    public function run()
    {
        $currentRegionId = $this->getCurrentRegionId();
        $entityTypes = $this->getEntityTypes();

        return $this->success([
            'landing' => $this->getLandingTypes(),
            'entityTypes' => $entityTypes,
            'depositTypes' => $this->getDepositTypes(),
            'eyeEntities' => $this->getEyeEntities($entityTypes, $currentRegionId),
            'deposits' => $this->getDeposits($currentRegionId),
            'resources' => $this->getResources(),
            'recipes' => $this->getRecipes(),
            'entityTypeRecipes' => $this->getEntityTypeRecipes(),
            'entityTypeCosts' => $this->getEntityTypeCosts(),
            'userResources' => $this->getUserResources(),
            'entityResources' => $this->getEntityResources($currentRegionId),
            'craftingStates' => $this->getCraftingStates($currentRegionId),
            'transportStates' => $this->getTransportStates($currentRegionId),
            'region' => $this->getRegion($currentRegionId),
            'buildPanel' => $this->getBuildPanel(),
            'cameraPosition' => $this->getCameraPosition(),
            'config' => $this->getConfig($currentRegionId),
            'buildingRules' => $this->getBuildingRules(),
        ]);
    }
}
