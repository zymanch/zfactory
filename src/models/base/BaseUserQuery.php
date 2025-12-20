<?php

namespace models\base;
use ActiveGenerator\Criteria;
use models\UserQuery;

/**
 * This is the ActiveQuery class for [[models\User]].
 * @method UserQuery filterByUserId($value, $criteria = null)
 * @method UserQuery filterByUsername($value, $criteria = null)
 * @method UserQuery filterByPassword($value, $criteria = null)
 * @method UserQuery filterByEmail($value, $criteria = null)
 * @method UserQuery filterByBuildPanel($value, $criteria = null)
 * @method UserQuery filterByCameraX($value, $criteria = null)
 * @method UserQuery filterByCameraY($value, $criteria = null)
 * @method UserQuery filterByZoom($value, $criteria = null)
 * @method UserQuery filterByCreatedAt($value, $criteria = null)
 * @method UserQuery filterByUpdatedAt($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByUserId($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByUsername($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByPassword($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByEmail($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByBuildPanel($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByCameraX($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByCameraY($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByZoom($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByCreatedAt($value, $criteria = null)
  * @method UserQuery andJoinOnConditionByUpdatedAt($value, $criteria = null)
  * @method UserQuery orderByUserId($order = Criteria::ASC)
  * @method UserQuery orderByUsername($order = Criteria::ASC)
  * @method UserQuery orderByPassword($order = Criteria::ASC)
  * @method UserQuery orderByEmail($order = Criteria::ASC)
  * @method UserQuery orderByBuildPanel($order = Criteria::ASC)
  * @method UserQuery orderByCameraX($order = Criteria::ASC)
  * @method UserQuery orderByCameraY($order = Criteria::ASC)
  * @method UserQuery orderByZoom($order = Criteria::ASC)
  * @method UserQuery orderByCreatedAt($order = Criteria::ASC)
  * @method UserQuery orderByUpdatedAt($order = Criteria::ASC)
 */
class BaseUserQuery extends \yii\db\ActiveQuery
{


    use \ActiveGenerator\base\RichActiveMethods;

    /**
     * @inheritdoc
     * @return \models\User[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return \models\User|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

    /**
     * @return \models\UserQuery     */
    public static function model()
    {
        return new \models\UserQuery(\models\User::class);
    }
}
