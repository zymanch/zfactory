<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\TechnologyUnlockEntityTypeQuery;

/**
 * This is the ActiveQuery class for [[models\TechnologyUnlockEntityType]].
 * @method TechnologyUnlockEntityTypeQuery filterByTechnologyId($value, $criteria = null)
 * @method TechnologyUnlockEntityTypeQuery filterByEntityTypeId($value, $criteria = null)
  * @method TechnologyUnlockEntityTypeQuery andJoinOnConditionByTechnologyId($value, $criteria = null)
  * @method TechnologyUnlockEntityTypeQuery andJoinOnConditionByEntityTypeId($value, $criteria = null)
  * @method TechnologyUnlockEntityTypeQuery orderByTechnologyId($order = Criteria::ASC)
  * @method TechnologyUnlockEntityTypeQuery orderByEntityTypeId($order = Criteria::ASC)
  * @method TechnologyUnlockEntityTypeQuery withTechnology($params = [])
  * @method TechnologyUnlockEntityTypeQuery joinWithTechnology($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyUnlockEntityTypeQuery withEntityType($params = [])
  * @method TechnologyUnlockEntityTypeQuery joinWithEntityType($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseTechnologyUnlockEntityTypeQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\TechnologyUnlockEntityType[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\TechnologyUnlockEntityType|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\TechnologyUnlockEntityTypeQuery     */
    public static function model()
    {
        return new \models\TechnologyUnlockEntityTypeQuery(\models\TechnologyUnlockEntityType::class);
    }
}
