<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\DepositTypeQuery;

/**
 * This is the ActiveQuery class for [[models\DepositType]].
 * @method DepositTypeQuery filterByDepositTypeId($value, $criteria = null)
 * @method DepositTypeQuery filterByType($value, $criteria = null)
 * @method DepositTypeQuery filterByName($value, $criteria = null)
 * @method DepositTypeQuery filterByDescription($value, $criteria = null)
 * @method DepositTypeQuery filterByImageUrl($value, $criteria = null)
 * @method DepositTypeQuery filterByExtension($value, $criteria = null)
 * @method DepositTypeQuery filterByMaxDurability($value, $criteria = null)
 * @method DepositTypeQuery filterByWidth($value, $criteria = null)
 * @method DepositTypeQuery filterByHeight($value, $criteria = null)
 * @method DepositTypeQuery filterByIconUrl($value, $criteria = null)
 * @method DepositTypeQuery filterByResourceId($value, $criteria = null)
 * @method DepositTypeQuery filterByResourceAmount($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByDepositTypeId($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByType($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByName($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByDescription($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByImageUrl($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByExtension($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByMaxDurability($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByWidth($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByHeight($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByIconUrl($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByResourceId($value, $criteria = null)
  * @method DepositTypeQuery andJoinOnConditionByResourceAmount($value, $criteria = null)
  * @method DepositTypeQuery orderByDepositTypeId($order = Criteria::ASC)
  * @method DepositTypeQuery orderByType($order = Criteria::ASC)
  * @method DepositTypeQuery orderByName($order = Criteria::ASC)
  * @method DepositTypeQuery orderByDescription($order = Criteria::ASC)
  * @method DepositTypeQuery orderByImageUrl($order = Criteria::ASC)
  * @method DepositTypeQuery orderByExtension($order = Criteria::ASC)
  * @method DepositTypeQuery orderByMaxDurability($order = Criteria::ASC)
  * @method DepositTypeQuery orderByWidth($order = Criteria::ASC)
  * @method DepositTypeQuery orderByHeight($order = Criteria::ASC)
  * @method DepositTypeQuery orderByIconUrl($order = Criteria::ASC)
  * @method DepositTypeQuery orderByResourceId($order = Criteria::ASC)
  * @method DepositTypeQuery orderByResourceAmount($order = Criteria::ASC)
  * @method DepositTypeQuery withDeposits($params = [])
  * @method DepositTypeQuery joinWithDeposits($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method DepositTypeQuery withResource($params = [])
  * @method DepositTypeQuery joinWithResource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseDepositTypeQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\DepositType[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\DepositType|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\DepositTypeQuery     */
    public static function model()
    {
        return new \models\DepositTypeQuery(\models\DepositType::class);
    }
}
