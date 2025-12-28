<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\DepositQuery;

/**
 * This is the ActiveQuery class for [[models\Deposit]].
 * @method DepositQuery filterByDepositId($value, $criteria = null)
 * @method DepositQuery filterByDepositTypeId($value, $criteria = null)
 * @method DepositQuery filterByX($value, $criteria = null)
 * @method DepositQuery filterByY($value, $criteria = null)
 * @method DepositQuery filterByResourceAmount($value, $criteria = null)
  * @method DepositQuery andJoinOnConditionByDepositId($value, $criteria = null)
  * @method DepositQuery andJoinOnConditionByDepositTypeId($value, $criteria = null)
  * @method DepositQuery andJoinOnConditionByX($value, $criteria = null)
  * @method DepositQuery andJoinOnConditionByY($value, $criteria = null)
  * @method DepositQuery andJoinOnConditionByResourceAmount($value, $criteria = null)
  * @method DepositQuery orderByDepositId($order = Criteria::ASC)
  * @method DepositQuery orderByDepositTypeId($order = Criteria::ASC)
  * @method DepositQuery orderByX($order = Criteria::ASC)
  * @method DepositQuery orderByY($order = Criteria::ASC)
  * @method DepositQuery orderByResourceAmount($order = Criteria::ASC)
  * @method DepositQuery withDepositType($params = [])
  * @method DepositQuery joinWithDepositType($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseDepositQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Deposit[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Deposit|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\DepositQuery     */
    public static function model()
    {
        return new \models\DepositQuery(\models\Deposit::class);
    }
}
