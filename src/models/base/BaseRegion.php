<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.region".
 *
 * @property integer $region_id
 * @property string $name
 * @property string $description
 * @property integer $difficulty
 * @property integer $x
 * @property integer $y
 * @property integer $width
 * @property integer $height
 * @property string $image_url
 * @property string $created_at
 * @property integer $ship_attach_x
 * @property integer $ship_attach_y
 *
 * @property \models\Deposit[] $deposits
 * @property \models\Entity[] $entities
 * @property \models\Map[] $maps
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
            [[BaseRegionPeer::NAME, BaseRegionPeer::X, BaseRegionPeer::Y, BaseRegionPeer::WIDTH, BaseRegionPeer::HEIGHT], 'required'],
            [[BaseRegionPeer::DESCRIPTION], 'string'],
            [[BaseRegionPeer::DIFFICULTY, BaseRegionPeer::X, BaseRegionPeer::Y, BaseRegionPeer::WIDTH, BaseRegionPeer::HEIGHT, BaseRegionPeer::SHIP_ATTACH_X, BaseRegionPeer::SHIP_ATTACH_Y], 'integer'],
            [[BaseRegionPeer::CREATED_AT], 'safe'],
            [[BaseRegionPeer::NAME], 'string', 'max' => 100],
            [[BaseRegionPeer::IMAGE_URL], 'string', 'max' => 255],
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
            BaseRegionPeer::DIFFICULTY => 'Difficulty',
            BaseRegionPeer::X => 'X',
            BaseRegionPeer::Y => 'Y',
            BaseRegionPeer::WIDTH => 'Width',
            BaseRegionPeer::HEIGHT => 'Height',
            BaseRegionPeer::IMAGE_URL => 'Image Url',
            BaseRegionPeer::CREATED_AT => 'Created At',
            BaseRegionPeer::SHIP_ATTACH_X => 'Ship Attach X',
            BaseRegionPeer::SHIP_ATTACH_Y => 'Ship Attach Y',
        ];
    }
    /**
     * @return \models\DepositQuery
     */
    public function getDeposits() {
        return $this->hasMany(\models\Deposit::className(), [BaseDepositPeer::REGION_ID => BaseRegionPeer::REGION_ID])->inverseOf('region');
    }
        /**
     * @return \models\EntityQuery
     */
    public function getEntities() {
        return $this->hasMany(\models\Entity::className(), [BaseEntityPeer::REGION_ID => BaseRegionPeer::REGION_ID])->inverseOf('region');
    }
        /**
     * @return \models\MapQuery
     */
    public function getMaps() {
        return $this->hasMany(\models\Map::className(), [BaseMapPeer::REGION_ID => BaseRegionPeer::REGION_ID])->inverseOf('region');
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
            'difficulty' => BaseRegionPeer::DIFFICULTY,
            'x' => BaseRegionPeer::X,
            'y' => BaseRegionPeer::Y,
            'width' => BaseRegionPeer::WIDTH,
            'height' => BaseRegionPeer::HEIGHT,
            'image_url' => BaseRegionPeer::IMAGE_URL,
            'created_at' => BaseRegionPeer::CREATED_AT,
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
            'deposits' => 'deposits',
            'entities' => 'entities',
            'maps' => 'maps',
            'users' => 'users',
            'userRegionVisits' => 'userRegionVisits',
            'userRegionVisits0' => 'userRegionVisits0',
            'users0' => 'users0',
        ];
        */
    }

}
