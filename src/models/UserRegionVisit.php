<?php

namespace models;

use models\base\BaseUserRegionVisit;
use Yii;

/**
 * This is the model class for table "user_region_visit".
 */
class UserRegionVisit extends BaseUserRegionVisit
{
    /**
     * Mark region as visited by user
     * @param int $userId
     * @param int $regionId
     * @param int $viewRadius
     * @param int|null $fromRegionId Region from which user traveled (only saved on first visit)
     * @return bool
     */
    public static function markVisited($userId, $regionId, $viewRadius, $fromRegionId = null)
    {
        $visit = static::findOne(['user_id' => $userId, 'region_id' => $regionId]);

        if (!$visit) {
            // First visit - save from_region_id to track route
            $visit = new static();
            $visit->user_id = $userId;
            $visit->region_id = $regionId;
            $visit->view_radius = $viewRadius;
            $visit->from_region_id = $fromRegionId;
        } else {
            // Subsequent visit - DO NOT update from_region_id (keep first route)
            // Update view radius if it increased (ship upgrade)
            if ($viewRadius > $visit->view_radius) {
                $visit->view_radius = $viewRadius;
            }
            // Update last visit timestamp
            $visit->last_visit_at = date('Y-m-d H:i:s');
        }

        return $visit->save();
    }

    /**
     * Get all visited region IDs for user
     * @param int $userId
     * @return array
     */
    public static function getVisitedRegionIds($userId)
    {
        return static::find()
            ->select('region_id')
            ->where(['user_id' => $userId])
            ->column();
    }

    /**
     * Check if user has visited region
     * @param int $userId
     * @param int $regionId
     * @return bool
     */
    public static function hasVisited($userId, $regionId)
    {
        return static::find()
            ->where(['user_id' => $userId, 'region_id' => $regionId])
            ->exists();
    }
}
