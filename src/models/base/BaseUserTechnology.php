<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.user_technology".
 *
 * @property integer $user_id
 * @property integer $technology_id
 * @property string $researched_at
 *
 * @property \models\Technology $technology
 * @property \models\User $user
 */
class BaseUserTechnology extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.user_technology';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseUserTechnologyPeer::USER_ID, BaseUserTechnologyPeer::TECHNOLOGY_ID], 'required'],
            [[BaseUserTechnologyPeer::USER_ID, BaseUserTechnologyPeer::TECHNOLOGY_ID], 'integer'],
            [[BaseUserTechnologyPeer::RESEARCHED_AT], 'safe'],
            [[BaseUserTechnologyPeer::TECHNOLOGY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseTechnology::className(), 'targetAttribute' => [BaseUserTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]],
            [[BaseUserTechnologyPeer::USER_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseUser::className(), 'targetAttribute' => [BaseUserTechnologyPeer::USER_ID => BaseUserPeer::USER_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseUserTechnologyPeer::USER_ID => 'User ID',
            BaseUserTechnologyPeer::TECHNOLOGY_ID => 'Technology ID',
            BaseUserTechnologyPeer::RESEARCHED_AT => 'Researched At',
        ];
    }
    /**
     * @return \models\TechnologyQuery
     */
    public function getTechnology() {
        return $this->hasOne(\models\Technology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseUserTechnologyPeer::TECHNOLOGY_ID]);
    }
        /**
     * @return \models\UserQuery
     */
    public function getUser() {
        return $this->hasOne(\models\User::className(), [BaseUserPeer::USER_ID => BaseUserTechnologyPeer::USER_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\UserTechnologyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\UserTechnologyQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'user_id' => BaseUserTechnologyPeer::USER_ID,
            'technology_id' => BaseUserTechnologyPeer::TECHNOLOGY_ID,
            'researched_at' => BaseUserTechnologyPeer::RESEARCHED_AT,
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
            'technology' => 'technology',
            'user' => 'user',
        ];
        */
    }

}
