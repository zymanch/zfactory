<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\EntityTypeCostQuery;

/**
 * This is the ActiveQuery class for [[models\EntityTypeCost]].
 * @method EntityTypeCostQuery filterByEntityTypeCostId($value, $criteria = null)
 * @method EntityTypeCostQuery filterByEntityTypeId($value, $criteria = null)
 * @method EntityTypeCostQuery filterByResourceId($value, $criteria = null)
 * @method EntityTypeCostQuery filterByQuantity($value, $criteria = null)
  * @method EntityTypeCostQuery andJoinOnConditionByEntityTypeCostId($value, $criteria = null)
  * @method EntityTypeCostQuery andJoinOnConditionByEntityTypeId($value, $criteria = null)
  * @method EntityTypeCostQuery andJoinOnConditionByResourceId($value, $criteria = null)
  * @method EntityTypeCostQuery andJoinOnConditionByQuantity($value, $criteria = null)
  * @method EntityTypeCostQuery orderByEntityTypeCostId($order = Criteria::ASC)
  * @method EntityTypeCostQuery orderByEntityTypeId($order = Criteria::ASC)
  * @method EntityTypeCostQuery orderByResourceId($order = Criteria::ASC)
  * @method EntityTypeCostQuery orderByQuantity($order = Criteria::ASC)
  * @method EntityTypeCostQuery withEntityType($params = [])
  * @method EntityTypeCostQuery joinWithEntityType($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityTypeCostQuery withResource($params = [])
  * @method EntityTypeCostQuery joinWithResource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseEntityTypeCostQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\EntityTypeCost[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\EntityTypeCost|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\EntityTypeCostQuery     */
    public static function model()
    {
        return new \models\EntityTypeCostQuery(\models\EntityTypeCost::class);
    }
}
