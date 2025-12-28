<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.deposit_type".
 *
 * @property integer $deposit_type_id
 * @property string $type
 * @property string $name
 * @property string $description
 * @property string $image_url
 * @property string $extension
 * @property integer $max_durability
 * @property integer $width
 * @property integer $height
 * @property string $icon_url
 * @property integer $resource_id
 * @property integer $resource_amount
 *
 * @property \models\Deposit[] $deposits
 * @property \models\Resource $resource
 */
class BaseDepositType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.deposit_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseDepositTypePeer::DEPOSIT_TYPE_ID, BaseDepositTypePeer::TYPE, BaseDepositTypePeer::NAME, BaseDepositTypePeer::IMAGE_URL, BaseDepositTypePeer::RESOURCE_ID], 'required'],
            [[BaseDepositTypePeer::DEPOSIT_TYPE_ID, BaseDepositTypePeer::MAX_DURABILITY, BaseDepositTypePeer::WIDTH, BaseDepositTypePeer::HEIGHT, BaseDepositTypePeer::RESOURCE_ID, BaseDepositTypePeer::RESOURCE_AMOUNT], 'integer'],
            [[BaseDepositTypePeer::TYPE, BaseDepositTypePeer::DESCRIPTION], 'string'],
            [[BaseDepositTypePeer::NAME], 'string', 'max' => 128],
            [[BaseDepositTypePeer::IMAGE_URL, BaseDepositTypePeer::ICON_URL], 'string', 'max' => 256],
            [[BaseDepositTypePeer::EXTENSION], 'string', 'max' => 4],
            [[BaseDepositTypePeer::RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseDepositTypePeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseDepositTypePeer::DEPOSIT_TYPE_ID => 'Deposit Type ID',
            BaseDepositTypePeer::TYPE => 'Type',
            BaseDepositTypePeer::NAME => 'Name',
            BaseDepositTypePeer::DESCRIPTION => 'Description',
            BaseDepositTypePeer::IMAGE_URL => 'Image Url',
            BaseDepositTypePeer::EXTENSION => 'Extension',
            BaseDepositTypePeer::MAX_DURABILITY => 'Max Durability',
            BaseDepositTypePeer::WIDTH => 'Width',
            BaseDepositTypePeer::HEIGHT => 'Height',
            BaseDepositTypePeer::ICON_URL => 'Icon Url',
            BaseDepositTypePeer::RESOURCE_ID => 'Resource ID',
            BaseDepositTypePeer::RESOURCE_AMOUNT => 'Resource Amount',
        ];
    }
    /**
     * @return \models\DepositQuery
     */
    public function getDeposits() {
        return $this->hasMany(\models\Deposit::className(), [BaseDepositPeer::DEPOSIT_TYPE_ID => BaseDepositTypePeer::DEPOSIT_TYPE_ID])->inverseOf('depositType');
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getResource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseDepositTypePeer::RESOURCE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\DepositTypeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\DepositTypeQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'deposit_type_id' => BaseDepositTypePeer::DEPOSIT_TYPE_ID,
            'type' => BaseDepositTypePeer::TYPE,
            'name' => BaseDepositTypePeer::NAME,
            'description' => BaseDepositTypePeer::DESCRIPTION,
            'image_url' => BaseDepositTypePeer::IMAGE_URL,
            'extension' => BaseDepositTypePeer::EXTENSION,
            'max_durability' => BaseDepositTypePeer::MAX_DURABILITY,
            'width' => BaseDepositTypePeer::WIDTH,
            'height' => BaseDepositTypePeer::HEIGHT,
            'icon_url' => BaseDepositTypePeer::ICON_URL,
            'resource_id' => BaseDepositTypePeer::RESOURCE_ID,
            'resource_amount' => BaseDepositTypePeer::RESOURCE_AMOUNT,
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
            'deposits' => 'deposits',
            'resource' => 'resource',
        ];
        */
    }

}
