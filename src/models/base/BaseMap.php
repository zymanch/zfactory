<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.map".
 *
 * @property integer $map_id
 * @property integer $landing_id
 * @property integer $x
 * @property integer $y
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
            [[BaseMapPeer::LANDING_ID, BaseMapPeer::X, BaseMapPeer::Y], 'required'],
            [[BaseMapPeer::LANDING_ID, BaseMapPeer::X, BaseMapPeer::Y], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseMapPeer::MAP_ID => 'Map ID',
            BaseMapPeer::LANDING_ID => 'Landing ID',
            BaseMapPeer::X => 'X',
            BaseMapPeer::Y => 'Y',
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
            'landing_id' => BaseMapPeer::LANDING_ID,
            'x' => BaseMapPeer::X,
            'y' => BaseMapPeer::Y,
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
