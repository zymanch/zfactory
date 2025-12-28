<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\EntityQuery;

/**
 * This is the ActiveQuery class for [[models\Entity]].
 * @method EntityQuery filterByEntityId($value, $criteria = null)
 * @method EntityQuery filterByEntityTypeId($value, $criteria = null)
 * @method EntityQuery filterByState($value, $criteria = null)
 * @method EntityQuery filterByDurability($value, $criteria = null)
 * @method EntityQuery filterByX($value, $criteria = null)
 * @method EntityQuery filterByY($value, $criteria = null)
 * @method EntityQuery filterByConstructionProgress($value, $criteria = null)
  * @method EntityQuery andJoinOnConditionByEntityId($value, $criteria = null)
  * @method EntityQuery andJoinOnConditionByEntityTypeId($value, $criteria = null)
  * @method EntityQuery andJoinOnConditionByState($value, $criteria = null)
  * @method EntityQuery andJoinOnConditionByDurability($value, $criteria = null)
  * @method EntityQuery andJoinOnConditionByX($value, $criteria = null)
  * @method EntityQuery andJoinOnConditionByY($value, $criteria = null)
  * @method EntityQuery andJoinOnConditionByConstructionProgress($value, $criteria = null)
  * @method EntityQuery orderByEntityId($order = Criteria::ASC)
  * @method EntityQuery orderByEntityTypeId($order = Criteria::ASC)
  * @method EntityQuery orderByState($order = Criteria::ASC)
  * @method EntityQuery orderByDurability($order = Criteria::ASC)
  * @method EntityQuery orderByX($order = Criteria::ASC)
  * @method EntityQuery orderByY($order = Criteria::ASC)
  * @method EntityQuery orderByConstructionProgress($order = Criteria::ASC)
  * @method EntityQuery withEntityCrafting($params = [])
  * @method EntityQuery joinWithEntityCrafting($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityQuery withEntityResources($params = [])
  * @method EntityQuery joinWithEntityResources($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityQuery withResources($params = [])
  * @method EntityQuery joinWithResources($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseEntityQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\Entity[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\Entity|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\EntityQuery     */
    public static function model()
    {
        return new \models\EntityQuery(\models\Entity::class);
    }
}
