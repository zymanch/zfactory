<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.landing_adjacency".
 *
 * @property integer $adjacency_id
 * @property integer $landing_id_1
 * @property integer $landing_id_2
 *
 * @property \models\Landing $landingId1
 * @property \models\Landing $landingId2
 */
class BaseLandingAdjacency extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.landing_adjacency';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseLandingAdjacencyPeer::LANDING_ID_1, BaseLandingAdjacencyPeer::LANDING_ID_2], 'required'],
            [[BaseLandingAdjacencyPeer::LANDING_ID_1, BaseLandingAdjacencyPeer::LANDING_ID_2], 'integer'],
            [[BaseLandingAdjacencyPeer::LANDING_ID_1, BaseLandingAdjacencyPeer::LANDING_ID_2], 'unique', 'targetAttribute' => [BaseLandingAdjacencyPeer::LANDING_ID_1, BaseLandingAdjacencyPeer::LANDING_ID_2]],
            [[BaseLandingAdjacencyPeer::LANDING_ID_1], 'exist', 'skipOnError' => true, 'targetClass' => BaseLanding::className(), 'targetAttribute' => [BaseLandingAdjacencyPeer::LANDING_ID_1 => BaseLandingPeer::LANDING_ID]],
            [[BaseLandingAdjacencyPeer::LANDING_ID_2], 'exist', 'skipOnError' => true, 'targetClass' => BaseLanding::className(), 'targetAttribute' => [BaseLandingAdjacencyPeer::LANDING_ID_2 => BaseLandingPeer::LANDING_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseLandingAdjacencyPeer::ADJACENCY_ID => 'Adjacency ID',
            BaseLandingAdjacencyPeer::LANDING_ID_1 => 'Landing Id 1',
            BaseLandingAdjacencyPeer::LANDING_ID_2 => 'Landing Id 2',
        ];
    }
    /**
     * @return \models\LandingQuery
     */
    public function getLandingId1() {
        return $this->hasOne(\models\Landing::className(), [BaseLandingPeer::LANDING_ID => BaseLandingAdjacencyPeer::LANDING_ID_1]);
    }
        /**
     * @return \models\LandingQuery
     */
    public function getLandingId2() {
        return $this->hasOne(\models\Landing::className(), [BaseLandingPeer::LANDING_ID => BaseLandingAdjacencyPeer::LANDING_ID_2]);
    }
    
    /**
     * @inheritdoc
     * @return \models\LandingAdjacencyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\LandingAdjacencyQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'adjacency_id' => BaseLandingAdjacencyPeer::ADJACENCY_ID,
            'landing_id_1' => BaseLandingAdjacencyPeer::LANDING_ID_1,
            'landing_id_2' => BaseLandingAdjacencyPeer::LANDING_ID_2,
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
            'landingId1' => 'landingId1',
            'landingId2' => 'landingId2',
        ];
        */
    }

}
