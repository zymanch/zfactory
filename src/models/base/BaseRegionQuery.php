<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\RegionQuery;

/**
 * This is the ActiveQuery class for [[models\Region]].
 * @method RegionQuery filterByRegionId($value, $criteria = null)
 * @method RegionQuery filterByName($value, $criteria = null)
 * @method RegionQuery filterByDescription($value, $criteria = null)
 * @method RegionQuery filterByDifficulty($value, $criteria = null)
 * @method RegionQuery filterByX($value, $criteria = null)
 * @method RegionQuery filterByY($value, $criteria = null)
 * @method RegionQuery filterByWidth($value, $criteria = null)
 * @method RegionQuery filterByHeight($value, $criteria = null)
 * @method RegionQuery filterByImageUrl($value, $criteria = null)
 * @method RegionQuery filterByCreatedAt($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByRegionId($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByName($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByDescription($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByDifficulty($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByX($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByY($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByWidth($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByHeight($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByImageUrl($value, $criteria = null)
  * @method RegionQuery andJoinOnConditionByCreatedAt($value, $criteria = null)
  * @method RegionQuery orderByRegionId($order = Criteria::ASC)
  * @method RegionQuery orderByName($order = Criteria::ASC)
  * @method RegionQuery orderByDescription($order = Criteria::ASC)
  * @method RegionQuery orderByDifficulty($order = Criteria::ASC)
  * @method RegionQuery orderByX($order = Criteria::ASC)
  * @method RegionQuery orderByY($order = Criteria::ASC)
  * @method RegionQuery orderByWidth($order = Criteria::ASC)
  * @method RegionQuery orderByHeight($order = Criteria::ASC)
  * @method RegionQuery orderByImageUrl($order = Criteria::ASC)
  * @method RegionQuery orderByCreatedAt($order = Criteria::ASC)
  * @method RegionQuery withDeposits($params = [])
  * @method RegionQuery joinWithDeposits($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RegionQuery withEntities($params = [])
  * @method RegionQuery joinWithEntities($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RegionQuery withMaps($params = [])
  * @method RegionQuery joinWithMaps($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RegionQuery withUsers($params = [])
  * @method RegionQuery joinWithUsers($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RegionQuery withUserRegionVisits($params = [])
  * @method RegionQuery joinWithUserRegionVisits($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RegionQuery withUserRegionVisits0($params = [])
  * @method RegionQuery joinWithUserRegionVisits0($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RegionQuery withUsers0($params = [])
  * @method RegionQuery joinWithUsers0($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseRegionQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Region[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Region|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\RegionQuery     */
    public static function model()
    {
        return new \models\RegionQuery(\models\Region::class);
    }
}
