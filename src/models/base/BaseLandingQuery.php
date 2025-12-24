<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\LandingQuery;

/**
 * This is the ActiveQuery class for [[models\Landing]].
 * @method LandingQuery filterByLandingId($value, $criteria = null)
 * @method LandingQuery filterByName($value, $criteria = null)
 * @method LandingQuery filterByIsBuildable($value, $criteria = null)
 * @method LandingQuery filterByFolder($value, $criteria = null)
 * @method LandingQuery filterByVariationsCount($value, $criteria = null)
 * @method LandingQuery filterByAiSeed($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByLandingId($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByName($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByIsBuildable($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByFolder($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByVariationsCount($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByAiSeed($value, $criteria = null)
  * @method LandingQuery orderByLandingId($order = Criteria::ASC)
  * @method LandingQuery orderByName($order = Criteria::ASC)
  * @method LandingQuery orderByIsBuildable($order = Criteria::ASC)
  * @method LandingQuery orderByFolder($order = Criteria::ASC)
  * @method LandingQuery orderByVariationsCount($order = Criteria::ASC)
  * @method LandingQuery orderByAiSeed($order = Criteria::ASC)
  * @method LandingQuery withLandingAdjacencies($params = [])
  * @method LandingQuery joinWithLandingAdjacencies($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method LandingQuery withLandingAdjacencies0($params = [])
  * @method LandingQuery joinWithLandingAdjacencies0($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method LandingQuery withLandingId2s($params = [])
  * @method LandingQuery joinWithLandingId2s($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method LandingQuery withLandingId1s($params = [])
  * @method LandingQuery joinWithLandingId1s($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseLandingQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Landing[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Landing|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\LandingQuery     */
    public static function model()
    {
        return new \models\LandingQuery(\models\Landing::class);
    }
}
