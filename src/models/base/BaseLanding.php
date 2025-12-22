<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.landing".
 *
 * @property integer $landing_id
 * @property string $is_buildable
 * @property string $image_url
 *
 * @property \models\LandingAdjacency[] $landingAdjacencies
 * @property \models\LandingAdjacency[] $landingAdjacencies0
 * @property \models\BaseLanding[] $landingId2s
 * @property \models\BaseLanding[] $landingId1s
 */
class BaseLanding extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.landing';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseLandingPeer::IS_BUILDABLE], 'string'],
            [[BaseLandingPeer::IMAGE_URL], 'required'],
            [[BaseLandingPeer::IMAGE_URL], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseLandingPeer::LANDING_ID => 'Landing ID',
            BaseLandingPeer::IS_BUILDABLE => 'Is Buildable',
            BaseLandingPeer::IMAGE_URL => 'Image Url',
        ];
    }
    /**
     * @return \models\LandingAdjacencyQuery
     */
    public function getLandingAdjacencies() {
        return $this->hasMany(\models\LandingAdjacency::className(), [BaseLandingAdjacencyPeer::LANDING_ID_1 => BaseLandingPeer::LANDING_ID])->inverseOf('landingId1');
    }
        /**
     * @return \models\LandingAdjacencyQuery
     */
    public function getLandingAdjacencies0() {
        return $this->hasMany(\models\LandingAdjacency::className(), [BaseLandingAdjacencyPeer::LANDING_ID_2 => BaseLandingPeer::LANDING_ID])->inverseOf('landingId2');
    }
        /**
     * @return \models\BaseLandingQuery
     */
    public function getLandingId2s() {
        return $this->hasMany(BaseLanding::className(), [BaseLandingPeer::LANDING_ID => BaseLandingAdjacencyPeer::LANDING_ID_2])->viaTable('landing_adjacency', [BaseLandingAdjacencyPeer::LANDING_ID_1 => BaseLandingPeer::LANDING_ID]);
    }
        /**
     * @return \models\BaseLandingQuery
     */
    public function getLandingId1s() {
        return $this->hasMany(BaseLanding::className(), [BaseLandingPeer::LANDING_ID => BaseLandingAdjacencyPeer::LANDING_ID_1])->viaTable('landing_adjacency', [BaseLandingAdjacencyPeer::LANDING_ID_2 => BaseLandingPeer::LANDING_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\LandingQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\LandingQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'landing_id' => BaseLandingPeer::LANDING_ID,
            'is_buildable' => BaseLandingPeer::IS_BUILDABLE,
            'image_url' => BaseLandingPeer::IMAGE_URL,
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
            'landingAdjacencies' => 'landingAdjacencies',
            'landingAdjacencies0' => 'landingAdjacencies0',
            'landingId2s' => 'landingId2s',
            'landingId1s' => 'landingId1s',
        ];
        */
    }

}
