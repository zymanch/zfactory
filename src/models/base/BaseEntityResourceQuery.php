<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\EntityResourceQuery;

/**
 * This is the ActiveQuery class for [[models\EntityResource]].
 * @method EntityResourceQuery filterByEntityResourceId($value, $criteria = null)
 * @method EntityResourceQuery filterByEntityId($value, $criteria = null)
 * @method EntityResourceQuery filterByResourceId($value, $criteria = null)
 * @method EntityResourceQuery filterByAmount($value, $criteria = null)
  * @method EntityResourceQuery andJoinOnConditionByEntityResourceId($value, $criteria = null)
  * @method EntityResourceQuery andJoinOnConditionByEntityId($value, $criteria = null)
  * @method EntityResourceQuery andJoinOnConditionByResourceId($value, $criteria = null)
  * @method EntityResourceQuery andJoinOnConditionByAmount($value, $criteria = null)
  * @method EntityResourceQuery orderByEntityResourceId($order = Criteria::ASC)
  * @method EntityResourceQuery orderByEntityId($order = Criteria::ASC)
  * @method EntityResourceQuery orderByResourceId($order = Criteria::ASC)
  * @method EntityResourceQuery orderByAmount($order = Criteria::ASC)
  * @method EntityResourceQuery withEntity($params = [])
  * @method EntityResourceQuery joinWithEntity($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityResourceQuery withResource($params = [])
  * @method EntityResourceQuery joinWithResource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseEntityResourceQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\EntityResource[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\EntityResource|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\EntityResourceQuery     */
    public static function model()
    {
        return new \models\EntityResourceQuery(\models\EntityResource::class);
    }
}
