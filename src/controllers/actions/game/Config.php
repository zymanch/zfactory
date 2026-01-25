<?php

namespace controllers\actions\game;

use controllers\actions\JsonAction;
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
use models\PipeSystem;
use models\PipeSystemMember;
use services\BuildingRules;
use services\behaviors\EntityBehaviorFactory;
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
        $landingTypes = Landing::find()->indexBy('landing_id')->all();
        $result = [];

        foreach ($landingTypes as $id => $landing) {
            $data = $landing->toArray();
            // Add icon URL for frontend (uses first variation _0.png)
            $data['icon_url'] = $landing->getIconUrl();
            $result[$id] = $data;
        }

        return $this->castNumericFieldsIndexed(
            $result,
            ['landing_id']
        );
    }

    protected function getEntityTypes()
    {
        $entityTypes = EntityType::find()->indexBy('entity_type_id')->all();
        $entityTypeCosts = $this->getEntityTypeCosts();
        $entityTypeRecipes = $this->getEntityTypeRecipes();
        $behaviors = EntityBehaviorFactory::getAllClientBehaviors();

        $result = [];
        foreach ($entityTypes as $id => $entityType) {
            $data = $entityType->toArray();
            // Add atlases for frontend
            $data['atlases'] = $entityType->getAtlases();

            // Convert SET fields to arrays for easier JS handling
            $data['input_connections'] = $data['input_connections']
                ? explode(',', $data['input_connections'])
                : [];
            $data['output_connections'] = $data['output_connections']
                ? explode(',', $data['output_connections'])
                : [];

            // Add costs, recipes, behavior
            $data['costs'] = $entityTypeCosts[$id] ?? [];
            $data['recipes'] = $entityTypeRecipes[$id] ?? [];
            $data['behavior'] = $behaviors[$id] ?? null;

            $result[$id] = $data;
        }

        return $this->castNumericFieldsIndexed(
            $result,
            ['entity_type_id', 'power', 'max_durability', 'width', 'height', 'storage_resource_count', 'storage_per_resource']
        );
    }

    protected function getDepositTypes()
    {
        $depositTypes = DepositType::find()->indexBy('deposit_type_id')->all();
        $result = [];

        foreach ($depositTypes as $id => $depositType) {
            $data = $depositType->toArray();
            // Add full path for frontend (deposits only have normal.png)
            $data['sprite_url'] = $depositType->getSpriteUrl();
            $result[$id] = $data;
        }

        return $this->castNumericFieldsIndexed(
            $result,
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
            'is_admin' => !$this->isGuest() && (bool)$this->getUser()->is_admin,
        ];
    }

    protected function getResources()
    {
        $resources = Resource::find()->indexBy('resource_id')->all();
        $result = [];

        foreach ($resources as $id => $resource) {
            $data = $resource->toArray();
            // Add full path for frontend (backend controls all paths)
            $data['icon_url'] = "/assets/tiles/resources/{$resource->icon_url}";
            $result[$id] = $data;
        }

        return $this->castNumericFieldsIndexed(
            $result,
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
        $v = Yii::$app->params['asset_version'];

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
            'rebuildEntityUrl' => \yii\helpers\Url::to(['game/rebuild-entity'], true),
            'regionsMapUrl' => \yii\helpers\Url::to(['regions/index'], true),
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
            // Sprite paths (for direct access in JS, not loaded as textures)
            'sprites' => [
                'electrification' => "/assets/tiles/electrification.png?v={$v}",
                'no_power' => "/assets/tiles/no_power.png?v={$v}",
            ],
        ];
    }

    protected function getBuildingRules()
    {
        return BuildingRules::getClientRules();
    }

    protected function getPipeSystems($currentRegionId)
    {
        $systems = PipeSystem::find()
            ->where(['region_id' => $currentRegionId])
            ->asArray()
            ->all();

        $result = [];
        foreach ($systems as $system) {
            $systemId = (int)$system['pipe_system_id'];

            // Get all entity_ids in this system
            $members = PipeSystemMember::find()
                ->where(['pipe_system_id' => $systemId])
                ->select(['entity_id'])
                ->asArray()
                ->all();

            $entityIds = array_map(function($m) { return (int)$m['entity_id']; }, $members);

            $result[$systemId] = [
                'pipe_system_id' => $systemId,
                'resource_id' => $system['resource_id'] ? (int)$system['resource_id'] : null,
                'current_amount' => (int)$system['current_amount'],
                'max_capacity' => (int)$system['max_capacity'],
                'entity_ids' => $entityIds,
            ];
        }

        return $result;
    }

    /**
     * Get asset manifest - ALL asset URLs with short keys
     * This centralizes all asset paths in backend (no hardcoded paths in JS)
     */
    protected function getAssetManifest()
    {
        $assets = [];
        $v = Yii::$app->params['asset_version'];

        // Landing atlases (10)
        // Removed individual tiles - use atlases only
        $landingTypes = $this->getLandingTypes();
        foreach ($landingTypes as $id => $landing) {
            $folder = $landing['folder'];
            $assets["landing_atlas_{$folder}"] = "/assets/tiles/landing/atlases/{$folder}_atlas.png?v={$v}";
        }

        // Entity atlases (300+)
        // Skip conveyor/underground_belt/splitter - they use orientation-specific atlases loaded separately
        $entityTypes = $this->getEntityTypes();
        foreach ($entityTypes as $id => $entityType) {
            $atlases = $entityType['atlases'];

            // Regular entities have 'default' atlas
            if (isset($atlases['default'])) {
                $assets["entity_atlas_{$id}"] = $atlases['default'] . "?v={$v}";
            }
            // Multi-atlas entities (conveyor, underground_belt, splitter) are skipped
            // They use orientation-specific atlases loaded separately below
        }

        // Deposit sprites (22)
        $depositTypes = $this->getDepositTypes();
        foreach ($depositTypes as $id => $depositType) {
            $assets["deposit_{$id}"] = $depositType['sprite_url'] . "?v={$v}";
        }

        // Resource icons (112)
        $resources = $this->getResources();
        foreach ($resources as $id => $resource) {
            // icon_url already contains full path from getResources()
            $assets["resource_{$id}"] = "{$resource['icon_url']}?v={$v}";
        }

        // Conveyor atlases (20: 5 states × 4 orientations)
        $states = ['normal', 'damaged', 'blueprint', 'normal_selected', 'damaged_selected'];
        $orientations = ['right', 'down', 'left', 'up'];
        foreach ($states as $state) {
            foreach ($orientations as $orient) {
                $key = "conveyor_{$state}_{$orient}";
                // Fix: folder is 'conveyor_left' not 'left', 'conveyor' for 'right'
                $folder = ($orient === 'right') ? 'conveyor' : "conveyor_{$orient}";
                $assets[$key] = "/assets/tiles/entities/conveyor/{$folder}/{$state}_atlas.png?v={$v}";
            }
        }

        // Underground belt atlases (40: 5 states × 8 orientations)
        $undergroundOrientations = [
            'underground_belt_in', 'underground_belt_in_down', 'underground_belt_in_left', 'underground_belt_in_up',
            'underground_belt_out', 'underground_belt_out_down', 'underground_belt_out_left', 'underground_belt_out_up'
        ];
        foreach ($states as $state) {
            foreach ($undergroundOrientations as $folder) {
                $key = "conveyor_{$state}_{$folder}";
                $assets[$key] = "/assets/tiles/entities/conveyor/{$folder}/{$state}_atlas.png?v={$v}";
            }
        }

        // Pipe atlases (16: 4 states × 4 folders)
        // Note: Pipes don't have blueprint state (only normal, damaged, normal_selected, damaged_selected)
        $pipeStates = ['normal', 'damaged', 'normal_selected', 'damaged_selected'];
        $pipeFolders = ['pipe', 'pipe_vertical', 'underground_pipe_in', 'underground_pipe_out'];
        foreach ($pipeStates as $state) {
            foreach ($pipeFolders as $folder) {
                $key = "pipe_{$folder}_{$state}";
                $assets[$key] = "/assets/tiles/entities/pipe/{$folder}/pipe_atlas_{$state}.png?v={$v}";
            }
        }

        // Special textures
        $assets['clouds_atlas'] = "/assets/clouds/clouds_atlas.png?v={$v}";
        $assets['electrification'] = "/assets/tiles/electrification.png?v={$v}";
        $assets['no_power'] = "/assets/tiles/no_power.png?v={$v}";

        // Region images
        $regions = \models\Region::find()->asArray()->all();
        foreach ($regions as $region) {
            if (!empty($region['image_url'])) {
                $assets["region_{$region['region_id']}"] = "/assets/images/regions/{$region['image_url']}?v={$v}";
            }
        }

        // Technology icons
        $technologies = \models\Technology::find()->asArray()->all();
        foreach ($technologies as $tech) {
            if (!empty($tech['icon'])) {
                $assets["technology_{$tech['technology_id']}"] = "/assets/tiles/technologies/{$tech['icon']}?v={$v}";
            }
        }

        // Pipe inlet atlas
        $assets['pipe_inlet_atlas'] = "/assets/tiles/pipe_inlets/inlet_atlas.png?v={$v}";

        return $assets;
    }

    public function run()
    {
        $currentRegionId = $this->getCurrentRegionId();

        return $this->success([
            'landing' => $this->getLandingTypes(),
            'entityTypes' => $this->getEntityTypes(),  // Теперь включает costs, recipes, behavior
            'depositTypes' => $this->getDepositTypes(),
            // УБРАЛИ: eyeEntities (фильтруем на клиенте)
            'deposits' => $this->getDeposits($currentRegionId),
            'resources' => $this->getResources(),
            'recipes' => $this->getRecipes(),
            // УБРАЛИ: entityTypeRecipes (теперь в entityTypes)
            // УБРАЛИ: entityTypeCosts (теперь в entityTypes)
            'userResources' => $this->getUserResources(),
            // REMOVED (2026-01): entityResources, craftingStates, transportStates moved to /game/entities
            // УБРАЛИ: pipeSystems (перемещены в /game/entities)
            // УБРАЛИ: buildingRules (теперь в entityTypes)
            'region' => $this->getRegion($currentRegionId),
            'buildPanel' => $this->getBuildPanel(),
            'cameraPosition' => $this->getCameraPosition(),
            'config' => $this->getConfig($currentRegionId),
            'assetManifest' => $this->getAssetManifest(),  // NEW: All asset URLs
        ]);
    }
}
