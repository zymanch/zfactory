<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\TechnologyUnlockRecipeQuery;

/**
 * This is the ActiveQuery class for [[models\TechnologyUnlockRecipe]].
 * @method TechnologyUnlockRecipeQuery filterByTechnologyId($value, $criteria = null)
 * @method TechnologyUnlockRecipeQuery filterByRecipeId($value, $criteria = null)
  * @method TechnologyUnlockRecipeQuery andJoinOnConditionByTechnologyId($value, $criteria = null)
  * @method TechnologyUnlockRecipeQuery andJoinOnConditionByRecipeId($value, $criteria = null)
  * @method TechnologyUnlockRecipeQuery orderByTechnologyId($order = Criteria::ASC)
  * @method TechnologyUnlockRecipeQuery orderByRecipeId($order = Criteria::ASC)
  * @method TechnologyUnlockRecipeQuery withRecipe($params = [])
  * @method TechnologyUnlockRecipeQuery joinWithRecipe($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyUnlockRecipeQuery withTechnology($params = [])
  * @method TechnologyUnlockRecipeQuery joinWithTechnology($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseTechnologyUnlockRecipeQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\TechnologyUnlockRecipe[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\TechnologyUnlockRecipe|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\TechnologyUnlockRecipeQuery     */
    public static function model()
    {
        return new \models\TechnologyUnlockRecipeQuery(\models\TechnologyUnlockRecipe::class);
    }
}
