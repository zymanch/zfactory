<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.map".
 *
 * @property integer $map_id
 * @property integer $region_id
 * @property integer $landing_id
 * @property integer $x
 * @property integer $y
 * @property string $shake_intensity
 */
class BaseMap extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.map';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseMapPeer::REGION_ID, BaseMapPeer::LANDING_ID, BaseMapPeer::X, BaseMapPeer::Y], 'integer'],
            [[BaseMapPeer::LANDING_ID, BaseMapPeer::X, BaseMapPeer::Y], 'required'],
            [[BaseMapPeer::SHAKE_INTENSITY], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseMapPeer::MAP_ID => 'Map ID',
            BaseMapPeer::REGION_ID => 'Region ID',
            BaseMapPeer::LANDING_ID => 'Landing ID',
            BaseMapPeer::X => 'X',
            BaseMapPeer::Y => 'Y',
            BaseMapPeer::SHAKE_INTENSITY => 'Shake Intensity',
        ];
    }

    /**
     * @inheritdoc
     * @return \models\MapQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\MapQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'map_id' => BaseMapPeer::MAP_ID,
            'region_id' => BaseMapPeer::REGION_ID,
            'landing_id' => BaseMapPeer::LANDING_ID,
            'x' => BaseMapPeer::X,
            'y' => BaseMapPeer::Y,
            'shake_intensity' => BaseMapPeer::SHAKE_INTENSITY,
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
            ,
        ];
        */
    }

}
