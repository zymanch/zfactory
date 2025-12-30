<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\ShipEntityQuery;

/**
 * This is the ActiveQuery class for [[models\ShipEntity]].
 * @method ShipEntityQuery filterByShipEntityId($value, $criteria = null)
 * @method ShipEntityQuery filterByUserId($value, $criteria = null)
 * @method ShipEntityQuery filterByEntityTypeId($value, $criteria = null)
 * @method ShipEntityQuery filterByX($value, $criteria = null)
 * @method ShipEntityQuery filterByY($value, $criteria = null)
 * @method ShipEntityQuery filterByState($value, $criteria = null)
 * @method ShipEntityQuery filterByDurability($value, $criteria = null)
  * @method ShipEntityQuery andJoinOnConditionByShipEntityId($value, $criteria = null)
  * @method ShipEntityQuery andJoinOnConditionByUserId($value, $criteria = null)
  * @method ShipEntityQuery andJoinOnConditionByEntityTypeId($value, $criteria = null)
  * @method ShipEntityQuery andJoinOnConditionByX($value, $criteria = null)
  * @method ShipEntityQuery andJoinOnConditionByY($value, $criteria = null)
  * @method ShipEntityQuery andJoinOnConditionByState($value, $criteria = null)
  * @method ShipEntityQuery andJoinOnConditionByDurability($value, $criteria = null)
  * @method ShipEntityQuery orderByShipEntityId($order = Criteria::ASC)
  * @method ShipEntityQuery orderByUserId($order = Criteria::ASC)
  * @method ShipEntityQuery orderByEntityTypeId($order = Criteria::ASC)
  * @method ShipEntityQuery orderByX($order = Criteria::ASC)
  * @method ShipEntityQuery orderByY($order = Criteria::ASC)
  * @method ShipEntityQuery orderByState($order = Criteria::ASC)
  * @method ShipEntityQuery orderByDurability($order = Criteria::ASC)
  * @method ShipEntityQuery withEntityType($params = [])
  * @method ShipEntityQuery joinWithEntityType($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ShipEntityQuery withUser($params = [])
  * @method ShipEntityQuery joinWithUser($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseShipEntityQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\ShipEntity[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\ShipEntity|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\ShipEntityQuery     */
    public static function model()
    {
        return new \models\ShipEntityQuery(\models\ShipEntity::class);
    }
}
