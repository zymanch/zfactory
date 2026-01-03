<?php

namespace actions\admin;

use actions\JsonAction;
use models\Map;
use Yii;

/**
 * AJAX: Load map tiles for admin panel
 * Always requires region_id parameter
 */
class Tiles extends JsonAction
{
    public function run()
    {
        // Get region ID from query parameter (required)
        $regionId = (int)Yii::$app->request->get('region_id', 0);

        if (!$regionId) {
            return $this->error('region_id parameter is required');
        }

        // Get island tiles for specified region
        $tiles = $this->castNumericFieldsArray(
            Map::find()
                ->select(['map_id', 'landing_id', 'x', 'y'])
                ->where(['region_id' => $regionId])
                ->asArray()
                ->all(),
            ['map_id', 'landing_id', 'x', 'y']
        );

        return $this->success(['tiles' => $tiles]);
    }
}
