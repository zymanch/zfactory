<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\TechnologyCostQuery;

/**
 * This is the ActiveQuery class for [[models\TechnologyCost]].
 * @method TechnologyCostQuery filterByTechnologyId($value, $criteria = null)
 * @method TechnologyCostQuery filterByResourceId($value, $criteria = null)
 * @method TechnologyCostQuery filterByQuantity($value, $criteria = null)
  * @method TechnologyCostQuery andJoinOnConditionByTechnologyId($value, $criteria = null)
  * @method TechnologyCostQuery andJoinOnConditionByResourceId($value, $criteria = null)
  * @method TechnologyCostQuery andJoinOnConditionByQuantity($value, $criteria = null)
  * @method TechnologyCostQuery orderByTechnologyId($order = Criteria::ASC)
  * @method TechnologyCostQuery orderByResourceId($order = Criteria::ASC)
  * @method TechnologyCostQuery orderByQuantity($order = Criteria::ASC)
  * @method TechnologyCostQuery withResource($params = [])
  * @method TechnologyCostQuery joinWithResource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyCostQuery withTechnology($params = [])
  * @method TechnologyCostQuery joinWithTechnology($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseTechnologyCostQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\TechnologyCost[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\TechnologyCost|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\TechnologyCostQuery     */
    public static function model()
    {
        return new \models\TechnologyCostQuery(\models\TechnologyCost::class);
    }
}
