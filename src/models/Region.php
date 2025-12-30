<?php

namespace models;

use models\base\BaseRegion;

/**
 * This is the model class for table "region".
 */
class Region extends BaseRegion
{
    /**
     * Get distance from this region to another region (Pythagoras)
     * @param Region $otherRegion
     * @return float
     */
    public function getDistanceTo($otherRegion)
    {
        $dx = $this->x - $otherRegion->x;
        $dy = $this->y - $otherRegion->y;
        return sqrt($dx * $dx + $dy * $dy);
    }

    /**
     * Check if region is within ship's jump distance
     * @param int $shipJumpDistance
     * @param Region $fromRegion
     * @return bool
     */
    public function isWithinJumpDistance($shipJumpDistance, $fromRegion)
    {
        return $this->getDistanceTo($fromRegion) <= $shipJumpDistance;
    }

    /**
     * Check if region is within ship's view radius
     * @param int $viewRadius
     * @param Region $fromRegion
     * @return bool
     */
    public function isWithinViewRadius($viewRadius, $fromRegion)
    {
        return $this->getDistanceTo($fromRegion) <= $viewRadius;
    }

    /**
     * Get total resource count in this region
     * @return int
     */
    public function getTotalResourceCount()
    {
        $count = 0;

        // Count resources in all deposits (except trees which reset)
        foreach ($this->deposits as $deposit) {
            if ($deposit->depositType && $deposit->depositType->name !== 'Tree') {
                $count += $deposit->quantity;
            }
        }

        return $count;
    }
}
