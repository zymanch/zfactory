<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\UserTechnologyQuery;

/**
 * This is the ActiveQuery class for [[models\UserTechnology]].
 * @method UserTechnologyQuery filterByUserId($value, $criteria = null)
 * @method UserTechnologyQuery filterByTechnologyId($value, $criteria = null)
 * @method UserTechnologyQuery filterByResearchedAt($value, $criteria = null)
  * @method UserTechnologyQuery andJoinOnConditionByUserId($value, $criteria = null)
  * @method UserTechnologyQuery andJoinOnConditionByTechnologyId($value, $criteria = null)
  * @method UserTechnologyQuery andJoinOnConditionByResearchedAt($value, $criteria = null)
  * @method UserTechnologyQuery orderByUserId($order = Criteria::ASC)
  * @method UserTechnologyQuery orderByTechnologyId($order = Criteria::ASC)
  * @method UserTechnologyQuery orderByResearchedAt($order = Criteria::ASC)
  * @method UserTechnologyQuery withTechnology($params = [])
  * @method UserTechnologyQuery joinWithTechnology($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method UserTechnologyQuery withUser($params = [])
  * @method UserTechnologyQuery joinWithUser($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseUserTechnologyQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\UserTechnology[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\UserTechnology|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\UserTechnologyQuery     */
    public static function model()
    {
        return new \models\UserTechnologyQuery(\models\UserTechnology::class);
    }
}
