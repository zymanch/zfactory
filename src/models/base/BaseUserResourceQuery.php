<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\UserResourceQuery;

/**
 * This is the ActiveQuery class for [[models\UserResource]].
 * @method UserResourceQuery filterByUserResourceId($value, $criteria = null)
 * @method UserResourceQuery filterByUserId($value, $criteria = null)
 * @method UserResourceQuery filterByResourceId($value, $criteria = null)
 * @method UserResourceQuery filterByQuantity($value, $criteria = null)
  * @method UserResourceQuery andJoinOnConditionByUserResourceId($value, $criteria = null)
  * @method UserResourceQuery andJoinOnConditionByUserId($value, $criteria = null)
  * @method UserResourceQuery andJoinOnConditionByResourceId($value, $criteria = null)
  * @method UserResourceQuery andJoinOnConditionByQuantity($value, $criteria = null)
  * @method UserResourceQuery orderByUserResourceId($order = Criteria::ASC)
  * @method UserResourceQuery orderByUserId($order = Criteria::ASC)
  * @method UserResourceQuery orderByResourceId($order = Criteria::ASC)
  * @method UserResourceQuery orderByQuantity($order = Criteria::ASC)
  * @method UserResourceQuery withResource($params = [])
  * @method UserResourceQuery joinWithResource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method UserResourceQuery withUser($params = [])
  * @method UserResourceQuery joinWithUser($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseUserResourceQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\UserResource[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\UserResource|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\UserResourceQuery     */
    public static function model()
    {
        return new \models\UserResourceQuery(\models\UserResource::class);
    }
}
