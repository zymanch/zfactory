<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity_resource".
 *
 * @property integer $entity_resource_id
 * @property integer $entity_id
 * @property integer $resource_id
 * @property integer $amount
 *
 * @property \models\Entity $entity
 * @property \models\Resource $resource
 */
class BaseEntityResource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.entity_resource';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityResourcePeer::ENTITY_ID, BaseEntityResourcePeer::RESOURCE_ID], 'required'],
            [[BaseEntityResourcePeer::ENTITY_ID, BaseEntityResourcePeer::RESOURCE_ID, BaseEntityResourcePeer::AMOUNT], 'integer'],
            [[BaseEntityResourcePeer::ENTITY_ID, BaseEntityResourcePeer::RESOURCE_ID], 'unique', 'targetAttribute' => [BaseEntityResourcePeer::ENTITY_ID, BaseEntityResourcePeer::RESOURCE_ID]],
            [[BaseEntityResourcePeer::ENTITY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseEntity::className(), 'targetAttribute' => [BaseEntityResourcePeer::ENTITY_ID => BaseEntityPeer::ENTITY_ID]],
            [[BaseEntityResourcePeer::RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseEntityResourcePeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseEntityResourcePeer::ENTITY_RESOURCE_ID => 'Entity Resource ID',
            BaseEntityResourcePeer::ENTITY_ID => 'Entity ID',
            BaseEntityResourcePeer::RESOURCE_ID => 'Resource ID',
            BaseEntityResourcePeer::AMOUNT => 'Amount',
        ];
    }
    /**
     * @return \models\EntityQuery
     */
    public function getEntity() {
        return $this->hasOne(\models\Entity::className(), [BaseEntityPeer::ENTITY_ID => BaseEntityResourcePeer::ENTITY_ID]);
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getResource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseEntityResourcePeer::RESOURCE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\EntityResourceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityResourceQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'entity_resource_id' => BaseEntityResourcePeer::ENTITY_RESOURCE_ID,
            'entity_id' => BaseEntityResourcePeer::ENTITY_ID,
            'resource_id' => BaseEntityResourcePeer::RESOURCE_ID,
            'amount' => BaseEntityResourcePeer::AMOUNT,
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
