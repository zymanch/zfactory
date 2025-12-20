<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\MapQuery;

/**
 * This is the ActiveQuery class for [[models\Map]].
 * @method MapQuery filterByMapId($value, $criteria = null)
 * @method MapQuery filterByLandingId($value, $criteria = null)
 * @method MapQuery filterByX($value, $criteria = null)
 * @method MapQuery filterByY($value, $criteria = null)
  * @method MapQuery andJoinOnConditionByMapId($value, $criteria = null)
  * @method MapQuery andJoinOnConditionByLandingId($value, $criteria = null)
  * @method MapQuery andJoinOnConditionByX($value, $criteria = null)
  * @method MapQuery andJoinOnConditionByY($value, $criteria = null)
  * @method MapQuery orderByMapId($order = Criteria::ASC)
  * @method MapQuery orderByLandingId($order = Criteria::ASC)
  * @method MapQuery orderByX($order = Criteria::ASC)
  * @method MapQuery orderByY($order = Criteria::ASC)
 */
class BaseMapQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Map[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Map|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\MapQuery     */
    public static function model()
    {
        return new \models\MapQuery(\models\Map::class);
    }
}
