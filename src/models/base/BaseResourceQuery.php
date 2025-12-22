<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\ResourceQuery;

/**
 * This is the ActiveQuery class for [[models\Resource]].
 * @method ResourceQuery filterByResourceId($value, $criteria = null)
 * @method ResourceQuery filterByName($value, $criteria = null)
 * @method ResourceQuery filterByIconUrl($value, $criteria = null)
 * @method ResourceQuery filterByType($value, $criteria = null)
 * @method ResourceQuery filterByMaxStack($value, $criteria = null)
  * @method ResourceQuery andJoinOnConditionByResourceId($value, $criteria = null)
  * @method ResourceQuery andJoinOnConditionByName($value, $criteria = null)
  * @method ResourceQuery andJoinOnConditionByIconUrl($value, $criteria = null)
  * @method ResourceQuery andJoinOnConditionByType($value, $criteria = null)
  * @method ResourceQuery andJoinOnConditionByMaxStack($value, $criteria = null)
  * @method ResourceQuery orderByResourceId($order = Criteria::ASC)
  * @method ResourceQuery orderByName($order = Criteria::ASC)
  * @method ResourceQuery orderByIconUrl($order = Criteria::ASC)
  * @method ResourceQuery orderByType($order = Criteria::ASC)
  * @method ResourceQuery orderByMaxStack($order = Criteria::ASC)
  * @method ResourceQuery withEntityResources($params = [])
  * @method ResourceQuery joinWithEntityResources($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ResourceQuery withEntities($params = [])
  * @method ResourceQuery joinWithEntities($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ResourceQuery withEntityTransports($params = [])
  * @method ResourceQuery joinWithEntityTransports($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ResourceQuery withRecipes($params = [])
  * @method ResourceQuery joinWithRecipes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ResourceQuery withRecipes0($params = [])
  * @method ResourceQuery joinWithRecipes0($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ResourceQuery withRecipes1($params = [])
  * @method ResourceQuery joinWithRecipes1($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ResourceQuery withRecipes2($params = [])
  * @method ResourceQuery joinWithRecipes2($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseResourceQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Resource[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Resource|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\ResourceQuery     */
    public static function model()
    {
        return new \models\ResourceQuery(\models\Resource::class);
    }
}
