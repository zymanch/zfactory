<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.landing".
 *
 * @property integer $landing_id
 * @property string $name
 * @property string $is_buildable
 * @property string $folder
 * @property integer $variations_count
 * @property integer $ai_seed
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
            [[BaseLandingPeer::NAME, BaseLandingPeer::FOLDER], 'required'],
            [[BaseLandingPeer::IS_BUILDABLE], 'string'],
            [[BaseLandingPeer::VARIATIONS_COUNT, BaseLandingPeer::AI_SEED], 'integer'],
            [[BaseLandingPeer::NAME], 'string', 'max' => 64],
            [[BaseLandingPeer::FOLDER], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseLandingPeer::LANDING_ID => 'Landing ID',
            BaseLandingPeer::NAME => 'Name',
            BaseLandingPeer::IS_BUILDABLE => 'Is Buildable',
            BaseLandingPeer::FOLDER => 'Folder',
            BaseLandingPeer::VARIATIONS_COUNT => 'Variations Count',
            BaseLandingPeer::AI_SEED => 'Ai Seed',
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
            'name' => BaseLandingPeer::NAME,
            'is_buildable' => BaseLandingPeer::IS_BUILDABLE,
            'folder' => BaseLandingPeer::FOLDER,
            'variations_count' => BaseLandingPeer::VARIATIONS_COUNT,
            'ai_seed' => BaseLandingPeer::AI_SEED,
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
