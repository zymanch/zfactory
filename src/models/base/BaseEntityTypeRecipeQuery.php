<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\EntityTypeRecipeQuery;

/**
 * This is the ActiveQuery class for [[models\EntityTypeRecipe]].
 * @method EntityTypeRecipeQuery filterByEntityTypeId($value, $criteria = null)
 * @method EntityTypeRecipeQuery filterByRecipeId($value, $criteria = null)
  * @method EntityTypeRecipeQuery andJoinOnConditionByEntityTypeId($value, $criteria = null)
  * @method EntityTypeRecipeQuery andJoinOnConditionByRecipeId($value, $criteria = null)
  * @method EntityTypeRecipeQuery orderByEntityTypeId($order = Criteria::ASC)
  * @method EntityTypeRecipeQuery orderByRecipeId($order = Criteria::ASC)
  * @method EntityTypeRecipeQuery withEntityType($params = [])
  * @method EntityTypeRecipeQuery joinWithEntityType($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityTypeRecipeQuery withRecipe($params = [])
  * @method EntityTypeRecipeQuery joinWithRecipe($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseEntityTypeRecipeQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\EntityTypeRecipe[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\EntityTypeRecipe|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\EntityTypeRecipeQuery     */
    public static function model()
    {
        return new \models\EntityTypeRecipeQuery(\models\EntityTypeRecipe::class);
    }
}
