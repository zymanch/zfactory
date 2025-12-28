<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.deposit".
 *
 * @property integer $deposit_id
 * @property integer $deposit_type_id
 * @property integer $x
 * @property integer $y
 * @property integer $resource_amount
 *
 * @property \models\DepositType $depositType
 */
class BaseDeposit extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.deposit';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseDepositPeer::DEPOSIT_TYPE_ID, BaseDepositPeer::X, BaseDepositPeer::Y, BaseDepositPeer::RESOURCE_AMOUNT], 'required'],
            [[BaseDepositPeer::DEPOSIT_TYPE_ID, BaseDepositPeer::X, BaseDepositPeer::Y, BaseDepositPeer::RESOURCE_AMOUNT], 'integer'],
            [[BaseDepositPeer::DEPOSIT_TYPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseDepositType::className(), 'targetAttribute' => [BaseDepositPeer::DEPOSIT_TYPE_ID => BaseDepositTypePeer::DEPOSIT_TYPE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseDepositPeer::DEPOSIT_ID => 'Deposit ID',
            BaseDepositPeer::DEPOSIT_TYPE_ID => 'Deposit Type ID',
            BaseDepositPeer::X => 'X',
            BaseDepositPeer::Y => 'Y',
            BaseDepositPeer::RESOURCE_AMOUNT => 'Resource Amount',
        ];
    }
    /**
     * @return \models\DepositTypeQuery
     */
    public function getDepositType() {
        return $this->hasOne(\models\DepositType::className(), [BaseDepositTypePeer::DEPOSIT_TYPE_ID => BaseDepositPeer::DEPOSIT_TYPE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\DepositQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\DepositQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'deposit_id' => BaseDepositPeer::DEPOSIT_ID,
            'deposit_type_id' => BaseDepositPeer::DEPOSIT_TYPE_ID,
            'x' => BaseDepositPeer::X,
            'y' => BaseDepositPeer::Y,
            'resource_amount' => BaseDepositPeer::RESOURCE_AMOUNT,
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
            'depositType' => 'depositType',
        ];
        */
    }

}
