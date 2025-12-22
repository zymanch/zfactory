<?php

namespace actions\map;

use actions\JsonAction;
use models\Map;
use Yii;

/**
 * AJAX: Update landing tiles (admin tool for terrain editing)
 * POST params: changes[] - array of { x, y, landing_id } (null = delete)
 */
class UpdateLanding extends JsonAction
{
    public function run()
    {
        if (!Yii::$app->request->isPost) {
            return $this->error('POST required');
        }

        $data = $this->getBodyParams();
        $changes = $data['changes'] ?? [];

        if (empty($changes)) {
            return $this->error('No changes provided');
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $updated = [];
            $deleted = [];

            foreach ($changes as $change) {
                $x = (int)$change['x'];
                $y = (int)$change['y'];
                $landingId = $change['landing_id'];

                if ($landingId === null) {
                    // Delete tile (sky)
                    Map::deleteAll(['x' => $x, 'y' => $y]);
                    $deleted[] = ['x' => $x, 'y' => $y];
                } else {
                    // Update or insert
                    $tile = Map::findOne(['x' => $x, 'y' => $y]);
                    if ($tile) {
                        $tile->landing_id = (int)$landingId;
                        $tile->save();
                    } else {
                        $tile = new Map();
                        $tile->landing_id = (int)$landingId;
                        $tile->x = $x;
                        $tile->y = $y;
                        $tile->save();
                    }
                    $updated[] = ['x' => $x, 'y' => $y, 'landing_id' => (int)$landingId];
                }
            }

            $transaction->commit();

            return $this->success([
                'updated' => $updated,
                'deleted' => $deleted
            ]);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->error($e->getMessage());
        }
    }
}
