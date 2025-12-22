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
        // Get ALL map tiles
        $tiles = $this->castNumericFieldsArray(
            Map::find()
                ->select(['map_id', 'landing_id', 'x', 'y'])
                ->asArray()
                ->all(),
            ['map_id', 'landing_id', 'x', 'y']
        );

        return $this->success(['tiles' => $tiles]);
    }
}
