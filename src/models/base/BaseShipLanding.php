<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.ship_landing".
 *
 * @property integer $ship_landing_id
 * @property integer $user_id
 * @property integer $landing_id
 * @property integer $x
 * @property integer $y
 * @property integer $variation
 *
 * @property \models\Landing $landing
 * @property \models\User $user
 */
class BaseShipLanding extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.ship_landing';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseShipLandingPeer::USER_ID, BaseShipLandingPeer::LANDING_ID, BaseShipLandingPeer::X, BaseShipLandingPeer::Y], 'required'],
            [[BaseShipLandingPeer::USER_ID, BaseShipLandingPeer::LANDING_ID, BaseShipLandingPeer::X, BaseShipLandingPeer::Y, BaseShipLandingPeer::VARIATION], 'integer'],
            [[BaseShipLandingPeer::USER_ID, BaseShipLandingPeer::X, BaseShipLandingPeer::Y], 'unique', 'targetAttribute' => [BaseShipLandingPeer::USER_ID, BaseShipLandingPeer::X, BaseShipLandingPeer::Y]],
            [[BaseShipLandingPeer::LANDING_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseLanding::className(), 'targetAttribute' => [BaseShipLandingPeer::LANDING_ID => BaseLandingPeer::LANDING_ID]],
            [[BaseShipLandingPeer::USER_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseUser::className(), 'targetAttribute' => [BaseShipLandingPeer::USER_ID => BaseUserPeer::USER_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseShipLandingPeer::SHIP_LANDING_ID => 'Ship Landing ID',
            BaseShipLandingPeer::USER_ID => 'User ID',
            BaseShipLandingPeer::LANDING_ID => 'Landing ID',
            BaseShipLandingPeer::X => 'X',
            BaseShipLandingPeer::Y => 'Y',
            BaseShipLandingPeer::VARIATION => 'Variation',
        ];
    }
    /**
     * @return \models\LandingQuery
     */
    public function getLanding() {
        return $this->hasOne(\models\Landing::className(), [BaseLandingPeer::LANDING_ID => BaseShipLandingPeer::LANDING_ID]);
    }
        /**
     * @return \models\UserQuery
     */
    public function getUser() {
        return $this->hasOne(\models\User::className(), [BaseUserPeer::USER_ID => BaseShipLandingPeer::USER_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\ShipLandingQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\ShipLandingQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'ship_landing_id' => BaseShipLandingPeer::SHIP_LANDING_ID,
            'user_id' => BaseShipLandingPeer::USER_ID,
            'landing_id' => BaseShipLandingPeer::LANDING_ID,
            'x' => BaseShipLandingPeer::X,
            'y' => BaseShipLandingPeer::Y,
            'variation' => BaseShipLandingPeer::VARIATION,
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
            'landing' => 'landing',
            'user' => 'user',
        ];
        */
    }

}
