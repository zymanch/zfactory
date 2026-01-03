<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\TechnologyDependencyQuery;

/**
 * This is the ActiveQuery class for [[models\TechnologyDependency]].
 * @method TechnologyDependencyQuery filterByTechnologyId($value, $criteria = null)
 * @method TechnologyDependencyQuery filterByRequiredTechnologyId($value, $criteria = null)
  * @method TechnologyDependencyQuery andJoinOnConditionByTechnologyId($value, $criteria = null)
  * @method TechnologyDependencyQuery andJoinOnConditionByRequiredTechnologyId($value, $criteria = null)
  * @method TechnologyDependencyQuery orderByTechnologyId($order = Criteria::ASC)
  * @method TechnologyDependencyQuery orderByRequiredTechnologyId($order = Criteria::ASC)
  * @method TechnologyDependencyQuery withRequiredTechnology($params = [])
  * @method TechnologyDependencyQuery joinWithRequiredTechnology($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method TechnologyDependencyQuery withTechnology($params = [])
  * @method TechnologyDependencyQuery joinWithTechnology($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseTechnologyDependencyQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\TechnologyDependency[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\TechnologyDependency|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\TechnologyDependencyQuery     */
    public static function model()
    {
        return new \models\TechnologyDependencyQuery(\models\TechnologyDependency::class);
    }
}
