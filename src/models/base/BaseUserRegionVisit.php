<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.user_region_visit".
 *
 * @property integer $user_region_visit_id
 * @property integer $user_id
 * @property integer $region_id
 * @property integer $from_region_id
 * @property integer $view_radius
 * @property string $last_visit_at
 *
 * @property \models\Region $fromRegion
 * @property \models\Region $region
 * @property \models\User $user
 */
class BaseUserRegionVisit extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.user_region_visit';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseUserRegionVisitPeer::USER_ID, BaseUserRegionVisitPeer::REGION_ID, BaseUserRegionVisitPeer::VIEW_RADIUS], 'required'],
            [[BaseUserRegionVisitPeer::USER_ID, BaseUserRegionVisitPeer::REGION_ID, BaseUserRegionVisitPeer::FROM_REGION_ID, BaseUserRegionVisitPeer::VIEW_RADIUS], 'integer'],
            [[BaseUserRegionVisitPeer::LAST_VISIT_AT], 'safe'],
            [[BaseUserRegionVisitPeer::USER_ID, BaseUserRegionVisitPeer::REGION_ID], 'unique', 'targetAttribute' => [BaseUserRegionVisitPeer::USER_ID, BaseUserRegionVisitPeer::REGION_ID]],
            [[BaseUserRegionVisitPeer::FROM_REGION_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseRegion::className(), 'targetAttribute' => [BaseUserRegionVisitPeer::FROM_REGION_ID => BaseRegionPeer::REGION_ID]],
            [[BaseUserRegionVisitPeer::REGION_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseRegion::className(), 'targetAttribute' => [BaseUserRegionVisitPeer::REGION_ID => BaseRegionPeer::REGION_ID]],
            [[BaseUserRegionVisitPeer::USER_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseUser::className(), 'targetAttribute' => [BaseUserRegionVisitPeer::USER_ID => BaseUserPeer::USER_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseUserRegionVisitPeer::USER_REGION_VISIT_ID => 'User Region Visit ID',
            BaseUserRegionVisitPeer::USER_ID => 'User ID',
            BaseUserRegionVisitPeer::REGION_ID => 'Region ID',
            BaseUserRegionVisitPeer::FROM_REGION_ID => 'From Region ID',
            BaseUserRegionVisitPeer::VIEW_RADIUS => 'View Radius',
            BaseUserRegionVisitPeer::LAST_VISIT_AT => 'Last Visit At',
        ];
    }
    /**
     * @return \models\RegionQuery
     */
    public function getFromRegion() {
        return $this->hasOne(\models\Region::className(), [BaseRegionPeer::REGION_ID => BaseUserRegionVisitPeer::FROM_REGION_ID]);
    }
        /**
     * @return \models\RegionQuery
     */
    public function getRegion() {
        return $this->hasOne(\models\Region::className(), [BaseRegionPeer::REGION_ID => BaseUserRegionVisitPeer::REGION_ID]);
    }
        /**
     * @return \models\UserQuery
     */
    public function getUser() {
        return $this->hasOne(\models\User::className(), [BaseUserPeer::USER_ID => BaseUserRegionVisitPeer::USER_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\UserRegionVisitQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\UserRegionVisitQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'user_region_visit_id' => BaseUserRegionVisitPeer::USER_REGION_VISIT_ID,
            'user_id' => BaseUserRegionVisitPeer::USER_ID,
            'region_id' => BaseUserRegionVisitPeer::REGION_ID,
            'from_region_id' => BaseUserRegionVisitPeer::FROM_REGION_ID,
            'view_radius' => BaseUserRegionVisitPeer::VIEW_RADIUS,
            'last_visit_at' => BaseUserRegionVisitPeer::LAST_VISIT_AT,
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
            'fromRegion' => 'fromRegion',
            'region' => 'region',
            'user' => 'user',
        ];
        */
    }

}
