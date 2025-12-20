<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\LandingQuery;

/**
 * This is the ActiveQuery class for [[models\Landing]].
 * @method LandingQuery filterByLandingId($value, $criteria = null)
 * @method LandingQuery filterByIsBuildable($value, $criteria = null)
 * @method LandingQuery filterByImageUrl($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByLandingId($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByIsBuildable($value, $criteria = null)
  * @method LandingQuery andJoinOnConditionByImageUrl($value, $criteria = null)
  * @method LandingQuery orderByLandingId($order = Criteria::ASC)
  * @method LandingQuery orderByIsBuildable($order = Criteria::ASC)
  * @method LandingQuery orderByImageUrl($order = Criteria::ASC)
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
