<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity_type_cost".
 *
 * @property integer $entity_type_cost_id
 * @property integer $entity_type_id
 * @property integer $resource_id
 * @property integer $quantity
 *
 * @property \models\EntityType $entityType
 * @property \models\Resource $resource
 */
class BaseEntityTypeCost extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.entity_type_cost';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityTypeCostPeer::ENTITY_TYPE_ID, BaseEntityTypeCostPeer::RESOURCE_ID, BaseEntityTypeCostPeer::QUANTITY], 'required'],
            [[BaseEntityTypeCostPeer::ENTITY_TYPE_ID, BaseEntityTypeCostPeer::RESOURCE_ID, BaseEntityTypeCostPeer::QUANTITY], 'integer'],
            [[BaseEntityTypeCostPeer::ENTITY_TYPE_ID, BaseEntityTypeCostPeer::RESOURCE_ID], 'unique', 'targetAttribute' => [BaseEntityTypeCostPeer::ENTITY_TYPE_ID, BaseEntityTypeCostPeer::RESOURCE_ID]],
            [[BaseEntityTypeCostPeer::ENTITY_TYPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseEntityType::className(), 'targetAttribute' => [BaseEntityTypeCostPeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID]],
            [[BaseEntityTypeCostPeer::RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseEntityTypeCostPeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseEntityTypeCostPeer::ENTITY_TYPE_COST_ID => 'Entity Type Cost ID',
            BaseEntityTypeCostPeer::ENTITY_TYPE_ID => 'Entity Type ID',
            BaseEntityTypeCostPeer::RESOURCE_ID => 'Resource ID',
            BaseEntityTypeCostPeer::QUANTITY => 'Quantity',
        ];
    }
    /**
     * @return \models\EntityTypeQuery
     */
    public function getEntityType() {
        return $this->hasOne(\models\EntityType::className(), [BaseEntityTypePeer::ENTITY_TYPE_ID => BaseEntityTypeCostPeer::ENTITY_TYPE_ID]);
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getResource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseEntityTypeCostPeer::RESOURCE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\EntityTypeCostQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityTypeCostQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'entity_type_cost_id' => BaseEntityTypeCostPeer::ENTITY_TYPE_COST_ID,
            'entity_type_id' => BaseEntityTypeCostPeer::ENTITY_TYPE_ID,
            'resource_id' => BaseEntityTypeCostPeer::RESOURCE_ID,
            'quantity' => BaseEntityTypeCostPeer::QUANTITY,
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
            'entityType' => 'entityType',
            'resource' => 'resource',
        ];
        */
    }

}
