<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\ShipLandingQuery;

/**
 * This is the ActiveQuery class for [[models\ShipLanding]].
 * @method ShipLandingQuery filterByShipLandingId($value, $criteria = null)
 * @method ShipLandingQuery filterByUserId($value, $criteria = null)
 * @method ShipLandingQuery filterByLandingId($value, $criteria = null)
 * @method ShipLandingQuery filterByX($value, $criteria = null)
 * @method ShipLandingQuery filterByY($value, $criteria = null)
 * @method ShipLandingQuery filterByVariation($value, $criteria = null)
  * @method ShipLandingQuery andJoinOnConditionByShipLandingId($value, $criteria = null)
  * @method ShipLandingQuery andJoinOnConditionByUserId($value, $criteria = null)
  * @method ShipLandingQuery andJoinOnConditionByLandingId($value, $criteria = null)
  * @method ShipLandingQuery andJoinOnConditionByX($value, $criteria = null)
  * @method ShipLandingQuery andJoinOnConditionByY($value, $criteria = null)
  * @method ShipLandingQuery andJoinOnConditionByVariation($value, $criteria = null)
  * @method ShipLandingQuery orderByShipLandingId($order = Criteria::ASC)
  * @method ShipLandingQuery orderByUserId($order = Criteria::ASC)
  * @method ShipLandingQuery orderByLandingId($order = Criteria::ASC)
  * @method ShipLandingQuery orderByX($order = Criteria::ASC)
  * @method ShipLandingQuery orderByY($order = Criteria::ASC)
  * @method ShipLandingQuery orderByVariation($order = Criteria::ASC)
  * @method ShipLandingQuery withLanding($params = [])
  * @method ShipLandingQuery joinWithLanding($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method ShipLandingQuery withUser($params = [])
  * @method ShipLandingQuery joinWithUser($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseShipLandingQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\ShipLanding[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\ShipLanding|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\ShipLandingQuery     */
    public static function model()
    {
        return new \models\ShipLandingQuery(\models\ShipLanding::class);
    }
}
