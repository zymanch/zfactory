<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\EntityTransportQuery;

/**
 * This is the ActiveQuery class for [[models\EntityTransport]].
 * @method EntityTransportQuery filterByEntityId($value, $criteria = null)
 * @method EntityTransportQuery filterByResourceId($value, $criteria = null)
 * @method EntityTransportQuery filterByAmount($value, $criteria = null)
 * @method EntityTransportQuery filterByPosition($value, $criteria = null)
 * @method EntityTransportQuery filterByLateralOffset($value, $criteria = null)
 * @method EntityTransportQuery filterByArmPosition($value, $criteria = null)
 * @method EntityTransportQuery filterByStatus($value, $criteria = null)
  * @method EntityTransportQuery andJoinOnConditionByEntityId($value, $criteria = null)
  * @method EntityTransportQuery andJoinOnConditionByResourceId($value, $criteria = null)
  * @method EntityTransportQuery andJoinOnConditionByAmount($value, $criteria = null)
  * @method EntityTransportQuery andJoinOnConditionByPosition($value, $criteria = null)
  * @method EntityTransportQuery andJoinOnConditionByLateralOffset($value, $criteria = null)
  * @method EntityTransportQuery andJoinOnConditionByArmPosition($value, $criteria = null)
  * @method EntityTransportQuery andJoinOnConditionByStatus($value, $criteria = null)
  * @method EntityTransportQuery orderByEntityId($order = Criteria::ASC)
  * @method EntityTransportQuery orderByResourceId($order = Criteria::ASC)
  * @method EntityTransportQuery orderByAmount($order = Criteria::ASC)
  * @method EntityTransportQuery orderByPosition($order = Criteria::ASC)
  * @method EntityTransportQuery orderByLateralOffset($order = Criteria::ASC)
  * @method EntityTransportQuery orderByArmPosition($order = Criteria::ASC)
  * @method EntityTransportQuery orderByStatus($order = Criteria::ASC)
  * @method EntityTransportQuery withEntity($params = [])
  * @method EntityTransportQuery joinWithEntity($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityTransportQuery withResource($params = [])
  * @method EntityTransportQuery joinWithResource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseEntityTransportQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\EntityTransport[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\EntityTransport|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\EntityTransportQuery     */
    public static function model()
    {
        return new \models\EntityTransportQuery(\models\EntityTransport::class);
    }
}
