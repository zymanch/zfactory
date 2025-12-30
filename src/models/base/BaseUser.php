<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.user".
 *
 * @property integer $user_id
 * @property integer $current_region_id
 * @property integer $ship_view_radius
 * @property integer $ship_jump_distance
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $build_panel
 * @property integer $camera_x
 * @property integer $camera_y
 * @property double $zoom
 * @property string $created_at
 * @property string $updated_at
 *
 * @property \models\Region $currentRegion
 * @property \models\UserRegionVisit[] $userRegionVisits
 * @property \models\BaseRegion[] $regions
 * @property \models\UserResource[] $userResources
 * @property \models\BaseResource[] $resources
 */
class BaseUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseUserPeer::CURRENT_REGION_ID, BaseUserPeer::SHIP_VIEW_RADIUS, BaseUserPeer::SHIP_JUMP_DISTANCE, BaseUserPeer::CAMERA_X, BaseUserPeer::CAMERA_Y], 'integer'],
            [[BaseUserPeer::USERNAME, BaseUserPeer::PASSWORD, BaseUserPeer::EMAIL], 'required'],
            [[BaseUserPeer::BUILD_PANEL], 'string'],
            [[BaseUserPeer::ZOOM], 'number'],
            [[BaseUserPeer::CREATED_AT, BaseUserPeer::UPDATED_AT], 'safe'],
            [[BaseUserPeer::USERNAME], 'string', 'max' => 64],
            [[BaseUserPeer::PASSWORD], 'string', 'max' => 255],
            [[BaseUserPeer::EMAIL], 'string', 'max' => 128],
            [[BaseUserPeer::USERNAME], 'unique'],
            [[BaseUserPeer::EMAIL], 'unique'],
            [[BaseUserPeer::CURRENT_REGION_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseRegion::className(), 'targetAttribute' => [BaseUserPeer::CURRENT_REGION_ID => BaseRegionPeer::REGION_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseUserPeer::USER_ID => 'User ID',
            BaseUserPeer::CURRENT_REGION_ID => 'Current Region ID',
            BaseUserPeer::SHIP_VIEW_RADIUS => 'Ship View Radius',
            BaseUserPeer::SHIP_JUMP_DISTANCE => 'Ship Jump Distance',
            BaseUserPeer::USERNAME => 'Username',
            BaseUserPeer::PASSWORD => 'Password',
            BaseUserPeer::EMAIL => 'Email',
            BaseUserPeer::BUILD_PANEL => 'Build Panel',
            BaseUserPeer::CAMERA_X => 'Camera X',
            BaseUserPeer::CAMERA_Y => 'Camera Y',
            BaseUserPeer::ZOOM => 'Zoom',
            BaseUserPeer::CREATED_AT => 'Created At',
            BaseUserPeer::UPDATED_AT => 'Updated At',
        ];
    }
    /**
     * @return \models\RegionQuery
     */
    public function getCurrentRegion() {
        return $this->hasOne(\models\Region::className(), [BaseRegionPeer::REGION_ID => BaseUserPeer::CURRENT_REGION_ID]);
    }
        /**
     * @return \models\UserRegionVisitQuery
     */
    public function getUserRegionVisits() {
        return $this->hasMany(\models\UserRegionVisit::className(), [BaseUserRegionVisitPeer::USER_ID => BaseUserPeer::USER_ID])->inverseOf('user');
    }
        /**
     * @return \models\BaseRegionQuery
     */
    public function getRegions() {
        return $this->hasMany(BaseRegion::className(), [BaseRegionPeer::REGION_ID => BaseUserRegionVisitPeer::REGION_ID])->viaTable('user_region_visit', [BaseUserRegionVisitPeer::USER_ID => BaseUserPeer::USER_ID]);
    }
        /**
     * @return \models\UserResourceQuery
     */
    public function getUserResources() {
        return $this->hasMany(\models\UserResource::className(), [BaseUserResourcePeer::USER_ID => BaseUserPeer::USER_ID])->inverseOf('user');
    }
        /**
     * @return \models\BaseResourceQuery
     */
    public function getResources() {
        return $this->hasMany(BaseResource::className(), [BaseResourcePeer::RESOURCE_ID => BaseUserResourcePeer::RESOURCE_ID])->viaTable('user_resource', [BaseUserResourcePeer::USER_ID => BaseUserPeer::USER_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\UserQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\UserQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'user_id' => BaseUserPeer::USER_ID,
            'current_region_id' => BaseUserPeer::CURRENT_REGION_ID,
            'ship_view_radius' => BaseUserPeer::SHIP_VIEW_RADIUS,
            'ship_jump_distance' => BaseUserPeer::SHIP_JUMP_DISTANCE,
            'username' => BaseUserPeer::USERNAME,
            'password' => BaseUserPeer::PASSWORD,
            'email' => BaseUserPeer::EMAIL,
            'build_panel' => BaseUserPeer::BUILD_PANEL,
            'camera_x' => BaseUserPeer::CAMERA_X,
            'camera_y' => BaseUserPeer::CAMERA_Y,
            'zoom' => BaseUserPeer::ZOOM,
            'created_at' => BaseUserPeer::CREATED_AT,
            'updated_at' => BaseUserPeer::UPDATED_AT,
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
            'currentRegion' => 'currentRegion',
            'userRegionVisits' => 'userRegionVisits',
            'regions' => 'regions',
            'userResources' => 'userResources',
            'resources' => 'resources',
        ];
        */
    }

}
