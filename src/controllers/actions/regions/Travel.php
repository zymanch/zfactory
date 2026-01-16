<?php

namespace controllers\actions\regions;

use controllers\actions\JsonAction;
use models\Region;
use models\UserRegionVisit;
use Yii;

/**
 * API: Travel to another region
 */
class Travel extends JsonAction
{
    public function run()
    {
        $user = $this->getUser();
        $targetRegionId = (int)Yii::$app->request->post('region_id');

        $currentRegion = Region::findOne($user->current_region_id);
        $targetRegion = Region::findOne($targetRegionId);

        if (!$currentRegion) {
            return $this->error('Current region not found');
        }

        if (!$targetRegion) {
            return $this->error('Target region not found');
        }

        if ($targetRegionId === $currentRegion->region_id) {
            return $this->error('Already in this region');
        }

        // Check distance
        $distance = $currentRegion->getDistanceTo($targetRegion);

        if ($distance > $user->ship_jump_distance) {
            return $this->error('Region is too far to travel');
        }

        // Update user's current region and reset camera to ship attach area
        // ship_attach coordinates are in tiles, convert to pixels and offset slightly left
        $tileSize = Yii::$app->params['tile_width']; // 64px
        $offsetTiles = 10; // Start 10 tiles left of ship boundary to see the island

        $user->current_region_id = $targetRegionId;
        $user->camera_x = max(0, ($targetRegion->ship_attach_x - $offsetTiles) * $tileSize);
        $user->camera_y = $targetRegion->ship_attach_y * $tileSize;
        $user->zoom = 1; // Reset zoom to default
        if (!$user->save()) {
            return $this->error('Failed to update current region');
        }

        // Mark region as visited with route tracking (from_region_id)
        UserRegionVisit::markVisited($user->user_id, $targetRegionId, $user->ship_view_radius, $currentRegion->region_id);

        return $this->success([
            'current_region_id' => $user->current_region_id,
            'message' => "Traveled to {$targetRegion->name}",
        ]);
    }
}
