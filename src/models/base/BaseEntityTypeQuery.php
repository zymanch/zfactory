<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\EntityTypeQuery;

/**
 * This is the ActiveQuery class for [[models\EntityType]].
 * @method EntityTypeQuery filterByEntityTypeId($value, $criteria = null)
 * @method EntityTypeQuery filterByType($value, $criteria = null)
 * @method EntityTypeQuery filterByName($value, $criteria = null)
 * @method EntityTypeQuery filterByImageUrl($value, $criteria = null)
 * @method EntityTypeQuery filterByExtension($value, $criteria = null)
 * @method EntityTypeQuery filterByMaxDurability($value, $criteria = null)
 * @method EntityTypeQuery filterByWidth($value, $criteria = null)
 * @method EntityTypeQuery filterByHeight($value, $criteria = null)
 * @method EntityTypeQuery filterByIconUrl($value, $criteria = null)
 * @method EntityTypeQuery filterByPower($value, $criteria = null)
 * @method EntityTypeQuery filterByParentEntityTypeId($value, $criteria = null)
 * @method EntityTypeQuery filterByOrientation($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByEntityTypeId($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByType($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByName($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByImageUrl($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByExtension($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByMaxDurability($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByWidth($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByHeight($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByIconUrl($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByPower($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByParentEntityTypeId($value, $criteria = null)
  * @method EntityTypeQuery andJoinOnConditionByOrientation($value, $criteria = null)
  * @method EntityTypeQuery orderByEntityTypeId($order = Criteria::ASC)
  * @method EntityTypeQuery orderByType($order = Criteria::ASC)
  * @method EntityTypeQuery orderByName($order = Criteria::ASC)
  * @method EntityTypeQuery orderByImageUrl($order = Criteria::ASC)
  * @method EntityTypeQuery orderByExtension($order = Criteria::ASC)
  * @method EntityTypeQuery orderByMaxDurability($order = Criteria::ASC)
  * @method EntityTypeQuery orderByWidth($order = Criteria::ASC)
  * @method EntityTypeQuery orderByHeight($order = Criteria::ASC)
  * @method EntityTypeQuery orderByIconUrl($order = Criteria::ASC)
  * @method EntityTypeQuery orderByPower($order = Criteria::ASC)
  * @method EntityTypeQuery orderByParentEntityTypeId($order = Criteria::ASC)
  * @method EntityTypeQuery orderByOrientation($order = Criteria::ASC)
  * @method EntityTypeQuery withEntityTypeRecipes($params = [])
  * @method EntityTypeQuery joinWithEntityTypeRecipes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
  * @method EntityTypeQuery withRecipes($params = [])
  * @method EntityTypeQuery joinWithRecipes($params = null, $joinType = 'LEFT JOIN', $eagerLoading = true)
 */
class BaseEntityTypeQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\EntityType[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\EntityType|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\EntityTypeQuery     */
    public static function model()
    {
        return new \models\EntityTypeQuery(\models\EntityType::class);
    }
}
