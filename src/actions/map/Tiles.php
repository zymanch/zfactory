<?php

namespace actions\map;

use actions\JsonAction;
use models\Map;

/**
 * AJAX: Load all map tiles (single request on game init)
 */
class Tiles extends JsonAction
{
    public function run()
    {
        // Get current region ID
        $currentRegionId = 1; // Default
        if (!$this->isGuest()) {
            $currentRegionId = (int)$this->getUser()->current_region_id;
        }

        // Get ALL map tiles for current region
        $tiles = $this->castNumericFieldsArray(
            Map::find()
                ->select(['map_id', 'landing_id', 'x', 'y'])
                ->where(['region_id' => $currentRegionId])
                ->asArray()
                ->all(),
            ['map_id', 'landing_id', 'x', 'y']
        );

        return $this->success(['tiles' => $tiles]);
    }
}
