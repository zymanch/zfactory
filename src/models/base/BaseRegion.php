<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.region".
 *
 * @property integer $region_id
 * @property string $name
 * @property string $description
 * @property integer $width
 * @property integer $height
 * @property integer $seed
 * @property string $is_starter
 * @property integer $ship_attach_x
 * @property integer $ship_attach_y
 *
 * @property \models\User[] $users
 * @property \models\UserRegionVisit[] $userRegionVisits
 * @property \models\UserRegionVisit[] $userRegionVisits0
 * @property \models\BaseUser[] $users0
 */
class BaseRegion extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.region';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseRegionPeer::NAME], 'required'],
            [[BaseRegionPeer::DESCRIPTION, BaseRegionPeer::IS_STARTER], 'string'],
            [[BaseRegionPeer::WIDTH, BaseRegionPeer::HEIGHT, BaseRegionPeer::SEED, BaseRegionPeer::SHIP_ATTACH_X, BaseRegionPeer::SHIP_ATTACH_Y], 'integer'],
            [[BaseRegionPeer::NAME], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseRegionPeer::REGION_ID => 'Region ID',
            BaseRegionPeer::NAME => 'Name',
            BaseRegionPeer::DESCRIPTION => 'Description',
            BaseRegionPeer::WIDTH => 'Width',
            BaseRegionPeer::HEIGHT => 'Height',
            BaseRegionPeer::SEED => 'Seed',
            BaseRegionPeer::IS_STARTER => 'Is Starter',
            BaseRegionPeer::SHIP_ATTACH_X => 'Ship Attach X',
            BaseRegionPeer::SHIP_ATTACH_Y => 'Ship Attach Y',
        ];
    }
    /**
     * @return \models\UserQuery
     */
    public function getUsers() {
        return $this->hasMany(\models\User::className(), [BaseUserPeer::CURRENT_REGION_ID => BaseRegionPeer::REGION_ID])->inverseOf('currentRegion');
    }
        /**
     * @return \models\UserRegionVisitQuery
     */
    public function getUserRegionVisits() {
        return $this->hasMany(\models\UserRegionVisit::className(), [BaseUserRegionVisitPeer::FROM_REGION_ID => BaseRegionPeer::REGION_ID])->inverseOf('fromRegion');
    }
        /**
     * @return \models\UserRegionVisitQuery
     */
    public function getUserRegionVisits0() {
        return $this->hasMany(\models\UserRegionVisit::className(), [BaseUserRegionVisitPeer::REGION_ID => BaseRegionPeer::REGION_ID])->inverseOf('region');
    }
        /**
     * @return \models\BaseUserQuery
     */
    public function getUsers0() {
        return $this->hasMany(BaseUser::className(), [BaseUserPeer::USER_ID => BaseUserRegionVisitPeer::USER_ID])->viaTable('user_region_visit', [BaseUserRegionVisitPeer::REGION_ID => BaseRegionPeer::REGION_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\RegionQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\RegionQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'region_id' => BaseRegionPeer::REGION_ID,
            'name' => BaseRegionPeer::NAME,
            'description' => BaseRegionPeer::DESCRIPTION,
            'width' => BaseRegionPeer::WIDTH,
            'height' => BaseRegionPeer::HEIGHT,
            'seed' => BaseRegionPeer::SEED,
            'is_starter' => BaseRegionPeer::IS_STARTER,
            'ship_attach_x' => BaseRegionPeer::SHIP_ATTACH_X,
            'ship_attach_y' => BaseRegionPeer::SHIP_ATTACH_Y,
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
            'users' => 'users',
            'userRegionVisits' => 'userRegionVisits',
            'userRegionVisits0' => 'userRegionVisits0',
            'users0' => 'users0',
        ];
        */
    }

}
