<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.technology_cost".
 *
 * @property integer $technology_id
 * @property integer $resource_id
 * @property integer $quantity
 *
 * @property \models\Resource $resource
 * @property \models\Technology $technology
 */
class BaseTechnologyCost extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.technology_cost';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseTechnologyCostPeer::TECHNOLOGY_ID, BaseTechnologyCostPeer::RESOURCE_ID, BaseTechnologyCostPeer::QUANTITY], 'required'],
            [[BaseTechnologyCostPeer::TECHNOLOGY_ID, BaseTechnologyCostPeer::RESOURCE_ID, BaseTechnologyCostPeer::QUANTITY], 'integer'],
            [[BaseTechnologyCostPeer::RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseTechnologyCostPeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
            [[BaseTechnologyCostPeer::TECHNOLOGY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseTechnology::className(), 'targetAttribute' => [BaseTechnologyCostPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseTechnologyCostPeer::TECHNOLOGY_ID => 'Technology ID',
            BaseTechnologyCostPeer::RESOURCE_ID => 'Resource ID',
            BaseTechnologyCostPeer::QUANTITY => 'Quantity',
        ];
    }
    /**
     * @return \models\ResourceQuery
     */
    public function getResource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseTechnologyCostPeer::RESOURCE_ID]);
    }
        /**
     * @return \models\TechnologyQuery
     */
    public function getTechnology() {
        return $this->hasOne(\models\Technology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyCostPeer::TECHNOLOGY_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\TechnologyCostQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\TechnologyCostQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'technology_id' => BaseTechnologyCostPeer::TECHNOLOGY_ID,
            'resource_id' => BaseTechnologyCostPeer::RESOURCE_ID,
            'quantity' => BaseTechnologyCostPeer::QUANTITY,
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
            'resource' => 'resource',
            'technology' => 'technology',
        ];
        */
    }

}
