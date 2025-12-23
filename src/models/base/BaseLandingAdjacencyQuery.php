<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\LandingAdjacencyQuery;

/**
 * This is the ActiveQuery class for [[models\LandingAdjacency]].
 * @method LandingAdjacencyQuery filterByAdjacencyId($value, $criteria = null)
 * @method LandingAdjacencyQuery filterByLandingId1($value, $criteria = null)
 * @method LandingAdjacencyQuery filterByLandingId2($value, $criteria = null)
 * @method LandingAdjacencyQuery filterByAtlasZ($value, $criteria = null)
  * @method LandingAdjacencyQuery andJoinOnConditionByAdjacencyId($value, $criteria = null)
  * @method LandingAdjacencyQuery andJoinOnConditionByLandingId1($value, $criteria = null)
  * @method LandingAdjacencyQuery andJoinOnConditionByLandingId2($value, $criteria = null)
  * @method LandingAdjacencyQuery andJoinOnConditionByAtlasZ($value, $criteria = null)
  * @method LandingAdjacencyQuery orderByAdjacencyId($order = Criteria::ASC)
  * @method LandingAdjacencyQuery orderByLandingId1($order = Criteria::ASC)
  * @method LandingAdjacencyQuery orderByLandingId2($order = Criteria::ASC)
  * @method LandingAdjacencyQuery orderByAtlasZ($order = Criteria::ASC)
  * @method LandingAdjacencyQuery withLandingId1($params = [])
  * @method LandingAdjacencyQuery joinWithLandingId1($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method LandingAdjacencyQuery withLandingId2($params = [])
  * @method LandingAdjacencyQuery joinWithLandingId2($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseLandingAdjacencyQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\LandingAdjacency[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\LandingAdjacency|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\LandingAdjacencyQuery     */
    public static function model()
    {
        return new \models\LandingAdjacencyQuery(\models\LandingAdjacency::class);
    }
}
