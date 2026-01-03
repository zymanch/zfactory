<?php

namespace actions\admin;

use actions\JsonAction;
use models\Map;

class UpdateLanding extends JsonAction
{
    public function run()
    {
        $regionId = \Yii::$app->request->post('region_id');
        $changes = \Yii::$app->request->post('changes', []);

        if (!$regionId) {
            return $this->error('region_id required');
        }

        $updated = [];
        $deleted = [];

        foreach ($changes as $change) {
            $x = $change['x'] ?? null;
            $y = $change['y'] ?? null;
            $landingId = $change['landing_id'] ?? null;

            if ($x === null || $y === null) {
                continue;
            }

            // Find existing tile
            $tile = Map::find()
                ->where(['x' => $x, 'y' => $y, 'region_id' => $regionId])
                ->one();

            if ($landingId === null) {
                // Delete tile
                if ($tile) {
                    $tile->delete();
                    $deleted[] = ['x' => $x, 'y' => $y];
                }
            } else {
                // Create or update tile
                if (!$tile) {
                    $tile = new Map();
                    $tile->x = $x;
                    $tile->y = $y;
                    $tile->region_id = $regionId;
                }
                $tile->landing_id = $landingId;
                $tile->save(false);
                $updated[] = ['x' => $x, 'y' => $y, 'landing_id' => $landingId];
            }
        }

        return $this->success([
            'updated' => $updated,
            'deleted' => $deleted,
        ]);
    }
}
