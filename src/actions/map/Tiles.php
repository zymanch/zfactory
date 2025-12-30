<?php

namespace actions\map;

use actions\JsonAction;
use models\Map;
use models\ShipLanding;
use models\Region;

/**
 * AJAX: Load all map tiles (single request on game init)
 * Merges island (Map) and ship (ShipLanding) tiles into single array
 */
class Tiles extends JsonAction
{
    public function run()
    {
        // Get current region ID
        $currentRegionId = 1; // Default
        $userId = null;
        if (!$this->isGuest()) {
            $currentRegionId = (int)$this->getUser()->current_region_id;
            $userId = (int)$this->getUser()->user_id;
        }

        // Get island tiles for current region
        $islandTiles = $this->castNumericFieldsArray(
            Map::find()
                ->select(['map_id', 'landing_id', 'x', 'y'])
                ->where(['region_id' => $currentRegionId])
                ->asArray()
                ->all(),
            ['map_id', 'landing_id', 'x', 'y']
        );

        $tiles = $islandTiles;

        // Get ship tiles for current user (if logged in)
        if ($userId) {
            // Get region's ship attachment point
            $region = Region::findOne($currentRegionId);
            $shipAttachX = $region ? (int)$region->ship_attach_x : 0;
            $shipAttachY = $region ? (int)$region->ship_attach_y : 0;

            // Get ship landings
            $shipLandings = ShipLanding::find()
                ->select(['ship_landing_id', 'landing_id', 'x', 'y'])
                ->where(['user_id' => $userId])
                ->asArray()
                ->all();

            // Convert ship coordinates to world coordinates and add to tiles
            foreach ($shipLandings as $shipLanding) {
                $tiles[] = [
                    'map_id' => 'ship_' . $shipLanding['ship_landing_id'], // Prefix to distinguish from island tiles
                    'landing_id' => (int)$shipLanding['landing_id'],
                    'x' => (int)$shipLanding['x'] + $shipAttachX, // Convert to world coordinates
                    'y' => (int)$shipLanding['y'] + $shipAttachY,
                ];
            }
        }

        return $this->success(['tiles' => $tiles]);
    }
}
