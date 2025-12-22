<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\EntityCraftingQuery;

/**
 * This is the ActiveQuery class for [[models\EntityCrafting]].
 * @method EntityCraftingQuery filterByEntityId($value, $criteria = null)
 * @method EntityCraftingQuery filterByRecipeId($value, $criteria = null)
 * @method EntityCraftingQuery filterByTicksRemaining($value, $criteria = null)
  * @method EntityCraftingQuery andJoinOnConditionByEntityId($value, $criteria = null)
  * @method EntityCraftingQuery andJoinOnConditionByRecipeId($value, $criteria = null)
  * @method EntityCraftingQuery andJoinOnConditionByTicksRemaining($value, $criteria = null)
  * @method EntityCraftingQuery orderByEntityId($order = Criteria::ASC)
  * @method EntityCraftingQuery orderByRecipeId($order = Criteria::ASC)
  * @method EntityCraftingQuery orderByTicksRemaining($order = Criteria::ASC)
  * @method EntityCraftingQuery withEntity($params = [])
  * @method EntityCraftingQuery joinWithEntity($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityCraftingQuery withRecipe($params = [])
  * @method EntityCraftingQuery joinWithRecipe($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseEntityCraftingQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\EntityCrafting[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\EntityCrafting|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\EntityCraftingQuery     */
    public static function model()
    {
        return new \models\EntityCraftingQuery(\models\EntityCrafting::class);
    }
}
