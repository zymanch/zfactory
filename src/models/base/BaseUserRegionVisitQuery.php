<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\UserRegionVisitQuery;

/**
 * This is the ActiveQuery class for [[models\UserRegionVisit]].
 * @method UserRegionVisitQuery filterByUserRegionVisitId($value, $criteria = null)
 * @method UserRegionVisitQuery filterByUserId($value, $criteria = null)
 * @method UserRegionVisitQuery filterByRegionId($value, $criteria = null)
 * @method UserRegionVisitQuery filterByFromRegionId($value, $criteria = null)
 * @method UserRegionVisitQuery filterByViewRadius($value, $criteria = null)
 * @method UserRegionVisitQuery filterByLastVisitAt($value, $criteria = null)
  * @method UserRegionVisitQuery andJoinOnConditionByUserRegionVisitId($value, $criteria = null)
  * @method UserRegionVisitQuery andJoinOnConditionByUserId($value, $criteria = null)
  * @method UserRegionVisitQuery andJoinOnConditionByRegionId($value, $criteria = null)
  * @method UserRegionVisitQuery andJoinOnConditionByFromRegionId($value, $criteria = null)
  * @method UserRegionVisitQuery andJoinOnConditionByViewRadius($value, $criteria = null)
  * @method UserRegionVisitQuery andJoinOnConditionByLastVisitAt($value, $criteria = null)
  * @method UserRegionVisitQuery orderByUserRegionVisitId($order = Criteria::ASC)
  * @method UserRegionVisitQuery orderByUserId($order = Criteria::ASC)
  * @method UserRegionVisitQuery orderByRegionId($order = Criteria::ASC)
  * @method UserRegionVisitQuery orderByFromRegionId($order = Criteria::ASC)
  * @method UserRegionVisitQuery orderByViewRadius($order = Criteria::ASC)
  * @method UserRegionVisitQuery orderByLastVisitAt($order = Criteria::ASC)
  * @method UserRegionVisitQuery withFromRegion($params = [])
  * @method UserRegionVisitQuery joinWithFromRegion($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method UserRegionVisitQuery withRegion($params = [])
  * @method UserRegionVisitQuery joinWithRegion($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method UserRegionVisitQuery withUser($params = [])
  * @method UserRegionVisitQuery joinWithUser($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseUserRegionVisitQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\UserRegionVisit[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\UserRegionVisit|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\UserRegionVisitQuery     */
    public static function model()
    {
        return new \models\UserRegionVisitQuery(\models\UserRegionVisit::class);
    }
}
