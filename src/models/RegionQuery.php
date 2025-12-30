<?php

namespace models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[Region]].
 *
 * @see Region
 */
class RegionQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return Region[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return Region|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Find regions within view radius from a given region
     * @param Region $fromRegion
     * @param int $viewRadius
     * @return RegionQuery
     */
    public function withinViewRadius($fromRegion, $viewRadius)
    {
        $x = $fromRegion->x;
        $y = $fromRegion->y;

        // Approximate square for performance (exact distance checked in PHP)
        return $this->andWhere([
            'and',
            ['>=', 'x', $x - $viewRadius],
            ['<=', 'x', $x + $viewRadius],
            ['>=', 'y', $y - $viewRadius],
            ['<=', 'y', $y + $viewRadius],
        ]);
    }

    /**
     * Find regions by difficulty
     * @param int $difficulty
     * @return RegionQuery
     */
    public function byDifficulty($difficulty)
    {
        return $this->andWhere(['difficulty' => $difficulty]);
    }
}
