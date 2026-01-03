<?php

namespace actions\admin;

use actions\game\Config as GameConfig;
use Yii;

/**
 * AJAX: Get admin map editor config
 * Extends game config but excludes ship and entity-related data
 */
class Config extends GameConfig
{
    /**
     * Get region ID from query parameter instead of user's current_region_id
     */
    protected function getCurrentRegionId()
    {
        return (int) Yii::$app->request->get('region_id', 1);
    }

    /**
     * Override region data - include ship_attach for visual marker in admin
     */
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

    /**
     * Override config to exclude entity-related URLs and include region_id parameter
     */
    protected function getConfig($currentRegionId)
    {
        return [
            'mapUrl' => \yii\helpers\Url::to(['admin/tiles', 'region_id' => $currentRegionId], true),
            'depositsUrl' => \yii\helpers\Url::to(['game/deposits'], true),
            'tilesPath' => '/assets/tiles/',
            'currentRegionId' => $currentRegionId,
            'tileWidth' => Yii::$app->params['tile_width'],
            'tileHeight' => Yii::$app->params['tile_height'],
            'assetVersion' => Yii::$app->params['asset_version'],
            'debug' => Yii::$app->params['debug'] ?? false,
            'landingSkyId' => Yii::$app->params['landing_sky_id'],
            'landingBridgeId' => Yii::$app->params['landing_bridge_id'],
            'landingIslandEdgeId' => Yii::$app->params['landing_island_edge_id'],
            'landingShipEdgeId' => Yii::$app->params['landing_ship_edge_id'],
            'cameraSpeed' => 8,
        ];
    }

    /**
     * Override run() to return only admin-needed data
     */
    public function run()
    {
        $currentRegionId = $this->getCurrentRegionId();

        return $this->success([
            'landing' => $this->getLandingTypes(),
            'depositTypes' => $this->getDepositTypes(),
            'deposits' => $this->getDeposits($currentRegionId),
            'resources' => $this->getResources(),
            'region' => $this->getRegion($currentRegionId),
            'config' => $this->getConfig($currentRegionId),
        ]);
    }
}
