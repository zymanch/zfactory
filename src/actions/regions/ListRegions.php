<?php

namespace actions\regions;

use actions\JsonAction;
use models\Region;
use models\UserRegionVisit;
use Yii;

/**
 * API: Get all regions with visibility data
 */
class ListRegions extends JsonAction
{
    public function run()
    {
        $user = $this->getUser();
        $currentRegion = Region::findOne($user->current_region_id);

        if (!$currentRegion) {
            return $this->error('Current region not found');
        }

        // Get all regions
        $regions = Region::find()->asArray()->all();

        // Get visited regions with view_radius and from_region_id
        $visitedRegions = UserRegionVisit::find()
            ->select(['region_id', 'view_radius', 'from_region_id'])
            ->where(['user_id' => $user->user_id])
            ->asArray()
            ->all();

        $visitedData = [];
        foreach ($visitedRegions as $vr) {
            $visitedData[(int)$vr['region_id']] = [
                'view_radius' => (int)$vr['view_radius'],
                'from_region_id' => $vr['from_region_id'] ? (int)$vr['from_region_id'] : null,
            ];
        }
        $visitedIds = array_keys($visitedData);

        // Get resources with quantities for each region
        $depositResources = Yii::$app->db->createCommand('
            SELECT
                d.region_id,
                r.name,
                SUM(d.resource_amount) as total_amount
            FROM deposit d
            JOIN deposit_type dt ON d.deposit_type_id = dt.deposit_type_id
            JOIN resource r ON dt.resource_id = r.resource_id
            GROUP BY d.region_id, r.resource_id, r.name
            ORDER BY d.region_id, r.name
        ')->queryAll();

        $resourcesByRegion = [];
        foreach ($depositResources as $row) {
            $regionId = (int)$row['region_id'];
            if (!isset($resourcesByRegion[$regionId])) {
                $resourcesByRegion[$regionId] = [];
            }
            $amount = (int)$row['total_amount'];
            // Format amount with 'k' for thousands
            $formattedAmount = $amount >= 1000 ? round($amount / 1000, 1) . 'k' : $amount;
            $resourcesByRegion[$regionId][] = $row['name'] . ': ' . $formattedAmount;
        }

        // Convert arrays to newline-separated strings
        foreach ($resourcesByRegion as $regionId => $resources) {
            $resourcesByRegion[$regionId] = implode('<br>', $resources);
        }

        // Process each region
        $result = [];
        foreach ($regions as $region) {
            $regionId = (int)$region['region_id'];
            $isVisited = in_array($regionId, $visitedIds);
            $isCurrent = ($regionId === $currentRegion->region_id);

            // Calculate distance using Pythagoras
            $dx = $region['x'] - $currentRegion->x;
            $dy = $region['y'] - $currentRegion->y;
            $distance = sqrt($dx * $dx + $dy * $dy);

            // Check if within view radius
            $isVisible = $isVisited || ($distance <= $user->ship_view_radius);

            // Check if within jump distance
            $canTravel = $distance <= $user->ship_jump_distance;

            // Only include visible regions
            if (!$isVisible) {
                continue;
            }

            $resources = $resourcesByRegion[$regionId] ?? null;

            $visitInfo = $visitedData[$regionId] ?? null;

            $result[] = [
                'region_id' => $regionId,
                'name' => $region['name'],
                'description' => $region['description'],
                'difficulty' => (int)$region['difficulty'],
                'x' => (int)$region['x'],
                'y' => (int)$region['y'],
                'width' => (int)$region['width'],
                'height' => (int)$region['height'],
                'image_url' => $region['image_url'],
                'distance' => round($distance, 0),
                'is_visited' => $isVisited,
                'is_current' => $isCurrent,
                'can_travel' => $canTravel,
                'resources' => $resources,
                'visited_view_radius' => $isVisited && !$isCurrent ? ($visitInfo['view_radius'] ?? 0) : 0,
                'from_region_id' => $isVisited ? ($visitInfo['from_region_id'] ?? null) : null,
            ];
        }

        return $this->success([
            'regions' => $result,
            'current_region_id' => $currentRegion->region_id,
            'ship_view_radius' => $user->ship_view_radius,
            'ship_jump_distance' => $user->ship_jump_distance,
        ]);
    }
}
