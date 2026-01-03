<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\RecipeQuery;

/**
 * This is the ActiveQuery class for [[models\Recipe]].
 * @method RecipeQuery filterByRecipeId($value, $criteria = null)
 * @method RecipeQuery filterByOutputResourceId($value, $criteria = null)
 * @method RecipeQuery filterByOutputAmount($value, $criteria = null)
 * @method RecipeQuery filterByInput1ResourceId($value, $criteria = null)
 * @method RecipeQuery filterByInput1Amount($value, $criteria = null)
 * @method RecipeQuery filterByInput2ResourceId($value, $criteria = null)
 * @method RecipeQuery filterByInput2Amount($value, $criteria = null)
 * @method RecipeQuery filterByInput3ResourceId($value, $criteria = null)
 * @method RecipeQuery filterByInput3Amount($value, $criteria = null)
 * @method RecipeQuery filterByTicks($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByRecipeId($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByOutputResourceId($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByOutputAmount($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByInput1ResourceId($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByInput1Amount($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByInput2ResourceId($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByInput2Amount($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByInput3ResourceId($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByInput3Amount($value, $criteria = null)
  * @method RecipeQuery andJoinOnConditionByTicks($value, $criteria = null)
  * @method RecipeQuery orderByRecipeId($order = Criteria::ASC)
  * @method RecipeQuery orderByOutputResourceId($order = Criteria::ASC)
  * @method RecipeQuery orderByOutputAmount($order = Criteria::ASC)
  * @method RecipeQuery orderByInput1ResourceId($order = Criteria::ASC)
  * @method RecipeQuery orderByInput1Amount($order = Criteria::ASC)
  * @method RecipeQuery orderByInput2ResourceId($order = Criteria::ASC)
  * @method RecipeQuery orderByInput2Amount($order = Criteria::ASC)
  * @method RecipeQuery orderByInput3ResourceId($order = Criteria::ASC)
  * @method RecipeQuery orderByInput3Amount($order = Criteria::ASC)
  * @method RecipeQuery orderByTicks($order = Criteria::ASC)
  * @method RecipeQuery withEntityCraftings($params = [])
  * @method RecipeQuery joinWithEntityCraftings($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withEntityTypeRecipes($params = [])
  * @method RecipeQuery joinWithEntityTypeRecipes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withEntityTypes($params = [])
  * @method RecipeQuery joinWithEntityTypes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withInput1Resource($params = [])
  * @method RecipeQuery joinWithInput1Resource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withInput2Resource($params = [])
  * @method RecipeQuery joinWithInput2Resource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withInput3Resource($params = [])
  * @method RecipeQuery joinWithInput3Resource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withOutputResource($params = [])
  * @method RecipeQuery joinWithOutputResource($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withTechnologyUnlockRecipes($params = [])
  * @method RecipeQuery joinWithTechnologyUnlockRecipes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method RecipeQuery withTechnologies($params = [])
  * @method RecipeQuery joinWithTechnologies($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseRecipeQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Recipe[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Recipe|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\RecipeQuery     */
    public static function model()
    {
        return new \models\RecipeQuery(\models\Recipe::class);
    }
}
