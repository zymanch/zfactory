<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity_transport".
 *
 * @property integer $entity_id
 * @property integer $resource_id
 * @property integer $amount
 * @property string $position
 * @property string $lateral_offset
 * @property string $arm_position
 * @property string $status
 *
 * @property \models\Entity $entity
 * @property \models\Resource $resource
 */
class BaseEntityTransport extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.entity_transport';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityTransportPeer::ENTITY_ID], 'required'],
            [[BaseEntityTransportPeer::ENTITY_ID, BaseEntityTransportPeer::RESOURCE_ID, BaseEntityTransportPeer::AMOUNT], 'integer'],
            [[BaseEntityTransportPeer::POSITION, BaseEntityTransportPeer::LATERAL_OFFSET, BaseEntityTransportPeer::ARM_POSITION], 'number'],
            [[BaseEntityTransportPeer::STATUS], 'string'],
            [[BaseEntityTransportPeer::ENTITY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseEntity::className(), 'targetAttribute' => [BaseEntityTransportPeer::ENTITY_ID => BaseEntityPeer::ENTITY_ID]],
            [[BaseEntityTransportPeer::RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseEntityTransportPeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseEntityTransportPeer::ENTITY_ID => 'Entity ID',
            BaseEntityTransportPeer::RESOURCE_ID => 'Resource ID',
            BaseEntityTransportPeer::AMOUNT => 'Amount',
            BaseEntityTransportPeer::POSITION => 'Position',
            BaseEntityTransportPeer::LATERAL_OFFSET => 'Lateral Offset',
            BaseEntityTransportPeer::ARM_POSITION => 'Arm Position',
            BaseEntityTransportPeer::STATUS => 'Status',
        ];
    }
    /**
     * @return \models\EntityQuery
     */
    public function getEntity() {
        return $this->hasOne(\models\Entity::className(), [BaseEntityPeer::ENTITY_ID => BaseEntityTransportPeer::ENTITY_ID]);
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getResource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseEntityTransportPeer::RESOURCE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\EntityTransportQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityTransportQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'entity_id' => BaseEntityTransportPeer::ENTITY_ID,
            'resource_id' => BaseEntityTransportPeer::RESOURCE_ID,
            'amount' => BaseEntityTransportPeer::AMOUNT,
            'position' => BaseEntityTransportPeer::POSITION,
            'lateral_offset' => BaseEntityTransportPeer::LATERAL_OFFSET,
            'arm_position' => BaseEntityTransportPeer::ARM_POSITION,
            'status' => BaseEntityTransportPeer::STATUS,
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
            'entity' => 'entity',
            'resource' => 'resource',
        ];
        */
    }

}
