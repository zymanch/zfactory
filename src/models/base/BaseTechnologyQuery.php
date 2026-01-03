<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\TechnologyQuery;

/**
 * This is the ActiveQuery class for [[models\Technology]].
 * @method TechnologyQuery filterByTechnologyId($value, $criteria = null)
 * @method TechnologyQuery filterByName($value, $criteria = null)
 * @method TechnologyQuery filterByDescription($value, $criteria = null)
 * @method TechnologyQuery filterByIcon($value, $criteria = null)
 * @method TechnologyQuery filterByTier($value, $criteria = null)
  * @method TechnologyQuery andJoinOnConditionByTechnologyId($value, $criteria = null)
  * @method TechnologyQuery andJoinOnConditionByName($value, $criteria = null)
  * @method TechnologyQuery andJoinOnConditionByDescription($value, $criteria = null)
  * @method TechnologyQuery andJoinOnConditionByIcon($value, $criteria = null)
  * @method TechnologyQuery andJoinOnConditionByTier($value, $criteria = null)
  * @method TechnologyQuery orderByTechnologyId($order = Criteria::ASC)
  * @method TechnologyQuery orderByName($order = Criteria::ASC)
  * @method TechnologyQuery orderByDescription($order = Criteria::ASC)
  * @method TechnologyQuery orderByIcon($order = Criteria::ASC)
  * @method TechnologyQuery orderByTier($order = Criteria::ASC)
  * @method TechnologyQuery withTechnologyCosts($params = [])
  * @method TechnologyQuery joinWithTechnologyCosts($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withResources($params = [])
  * @method TechnologyQuery joinWithResources($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withTechnologyDependencies($params = [])
  * @method TechnologyQuery joinWithTechnologyDependencies($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withTechnologyDependencies0($params = [])
  * @method TechnologyQuery joinWithTechnologyDependencies0($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withTechnologies($params = [])
  * @method TechnologyQuery joinWithTechnologies($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withRequiredTechnologies($params = [])
  * @method TechnologyQuery joinWithRequiredTechnologies($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withTechnologyUnlockEntityTypes($params = [])
  * @method TechnologyQuery joinWithTechnologyUnlockEntityTypes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withEntityTypes($params = [])
  * @method TechnologyQuery joinWithEntityTypes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withTechnologyUnlockRecipes($params = [])
  * @method TechnologyQuery joinWithTechnologyUnlockRecipes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withRecipes($params = [])
  * @method TechnologyQuery joinWithRecipes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withUserTechnologies($params = [])
  * @method TechnologyQuery joinWithUserTechnologies($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyQuery withUsers($params = [])
  * @method TechnologyQuery joinWithUsers($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseTechnologyQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Technology[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Technology|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\TechnologyQuery     */
    public static function model()
    {
        return new \models\TechnologyQuery(\models\Technology::class);
    }
}
