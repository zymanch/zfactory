<?php

namespace models;

use yii\db\ActiveQuery;

/**
 * This is the ActiveQuery class for [[UserRegionVisit]].
 *
 * @see UserRegionVisit
 */
class UserRegionVisitQuery extends ActiveQuery
{
    /**
     * {@inheritdoc}
     * @return UserRegionVisit[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return UserRegionVisit|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * Find visits for specific user
     * @param int $userId
     * @return UserRegionVisitQuery
     */
    public function forUser($userId)
    {
        return $this->andWhere(['user_id' => $userId]);
    }

    /**
     * Find visits for specific region
     * @param int $regionId
     * @return UserRegionVisitQuery
     */
    public function forRegion($regionId)
    {
        return $this->andWhere(['region_id' => $regionId]);
    }
}
