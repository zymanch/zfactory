<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.ship_entity".
 *
 * @property integer $ship_entity_id
 * @property integer $user_id
 * @property integer $entity_type_id
 * @property integer $x
 * @property integer $y
 * @property string $state
 * @property integer $durability
 *
 * @property \models\EntityType $entityType
 * @property \models\User $user
 */
class BaseShipEntity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.ship_entity';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseShipEntityPeer::USER_ID, BaseShipEntityPeer::ENTITY_TYPE_ID, BaseShipEntityPeer::X, BaseShipEntityPeer::Y], 'required'],
            [[BaseShipEntityPeer::USER_ID, BaseShipEntityPeer::ENTITY_TYPE_ID, BaseShipEntityPeer::X, BaseShipEntityPeer::Y, BaseShipEntityPeer::DURABILITY], 'integer'],
            [[BaseShipEntityPeer::STATE], 'string'],
            [[BaseShipEntityPeer::ENTITY_TYPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseEntityType::className(), 'targetAttribute' => [BaseShipEntityPeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID]],
            [[BaseShipEntityPeer::USER_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseUser::className(), 'targetAttribute' => [BaseShipEntityPeer::USER_ID => BaseUserPeer::USER_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseShipEntityPeer::SHIP_ENTITY_ID => 'Ship Entity ID',
            BaseShipEntityPeer::USER_ID => 'User ID',
            BaseShipEntityPeer::ENTITY_TYPE_ID => 'Entity Type ID',
            BaseShipEntityPeer::X => 'X',
            BaseShipEntityPeer::Y => 'Y',
            BaseShipEntityPeer::STATE => 'State',
            BaseShipEntityPeer::DURABILITY => 'Durability',
        ];
    }
    /**
     * @return \models\EntityTypeQuery
     */
    public function getEntityType() {
        return $this->hasOne(\models\EntityType::className(), [BaseEntityTypePeer::ENTITY_TYPE_ID => BaseShipEntityPeer::ENTITY_TYPE_ID]);
    }
        /**
     * @return \models\UserQuery
     */
    public function getUser() {
        return $this->hasOne(\models\User::className(), [BaseUserPeer::USER_ID => BaseShipEntityPeer::USER_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\ShipEntityQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\ShipEntityQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'ship_entity_id' => BaseShipEntityPeer::SHIP_ENTITY_ID,
            'user_id' => BaseShipEntityPeer::USER_ID,
            'entity_type_id' => BaseShipEntityPeer::ENTITY_TYPE_ID,
            'x' => BaseShipEntityPeer::X,
            'y' => BaseShipEntityPeer::Y,
            'state' => BaseShipEntityPeer::STATE,
            'durability' => BaseShipEntityPeer::DURABILITY,
        ];
    }
    
    /**
    * @inheritdoc
    * @return array of relations available for rest query
    */
    public function getRestRelations()
    {
        /*
        return [
            'entityType' => 'entityType',
            'user' => 'user',
        ];
        */
    }

}
