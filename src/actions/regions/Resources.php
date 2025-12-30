<?php

namespace actions\regions;

use actions\JsonAction;
use models\Region;
use models\Deposit;
use Yii;

/**
 * API: Get total resources count in a region
 */
class Resources extends JsonAction
{
    public function run()
    {
        $regionId = (int)Yii::$app->request->get('region_id');

        $region = Region::findOne($regionId);

        if (!$region) {
            return $this->error('Region not found');
        }

        // Count resources by type
        $resources = [];
        $deposits = Deposit::find()
            ->where(['region_id' => $regionId])
            ->with('depositType')
            ->all();

        $totalResources = 0;

        foreach ($deposits as $deposit) {
            if (!$deposit->depositType) {
                continue;
            }

            $typeName = $deposit->depositType->name;

            // Trees reset on visit, don't count them
            if ($typeName === 'Tree') {
                continue;
            }

            if (!isset($resources[$typeName])) {
                $resources[$typeName] = 0;
            }

            $resources[$typeName] += $deposit->quantity;
            $totalResources += $deposit->quantity;
        }

        return $this->success([
            'region_id' => $regionId,
            'total_resources' => $totalResources,
            'resources_by_type' => $resources,
        ]);
    }
}
