<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.user_resource".
 *
 * @property integer $user_resource_id
 * @property integer $user_id
 * @property integer $resource_id
 * @property integer $quantity
 *
 * @property \models\Resource $resource
 * @property \models\User $user
 */
class BaseUserResource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.user_resource';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseUserResourcePeer::USER_ID, BaseUserResourcePeer::RESOURCE_ID], 'required'],
            [[BaseUserResourcePeer::USER_ID, BaseUserResourcePeer::RESOURCE_ID, BaseUserResourcePeer::QUANTITY], 'integer'],
            [[BaseUserResourcePeer::USER_ID, BaseUserResourcePeer::RESOURCE_ID], 'unique', 'targetAttribute' => [BaseUserResourcePeer::USER_ID, BaseUserResourcePeer::RESOURCE_ID]],
            [[BaseUserResourcePeer::RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseUserResourcePeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
            [[BaseUserResourcePeer::USER_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseUser::className(), 'targetAttribute' => [BaseUserResourcePeer::USER_ID => BaseUserPeer::USER_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseUserResourcePeer::USER_RESOURCE_ID => 'User Resource ID',
            BaseUserResourcePeer::USER_ID => 'User ID',
            BaseUserResourcePeer::RESOURCE_ID => 'Resource ID',
            BaseUserResourcePeer::QUANTITY => 'Quantity',
        ];
    }
    /**
     * @return \models\ResourceQuery
     */
    public function getResource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseUserResourcePeer::RESOURCE_ID]);
    }
        /**
     * @return \models\UserQuery
     */
    public function getUser() {
        return $this->hasOne(\models\User::className(), [BaseUserPeer::USER_ID => BaseUserResourcePeer::USER_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\UserResourceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\UserResourceQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'user_resource_id' => BaseUserResourcePeer::USER_RESOURCE_ID,
            'user_id' => BaseUserResourcePeer::USER_ID,
            'resource_id' => BaseUserResourcePeer::RESOURCE_ID,
            'quantity' => BaseUserResourcePeer::QUANTITY,
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
            'resource' => 'resource',
            'user' => 'user',
        ];
        */
    }

}
