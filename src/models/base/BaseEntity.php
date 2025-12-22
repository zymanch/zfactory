<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity".
 *
 * @property integer $entity_id
 * @property integer $entity_type_id
 * @property string $state
 * @property integer $durability
 * @property integer $x
 * @property integer $y
 *
 * @property \models\EntityCrafting $entityCrafting
 * @property \models\EntityResource[] $entityResources
 * @property \models\BaseResource[] $resources
 * @property \models\EntityTransport $entityTransport
 */
class BaseEntity extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.entity';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityPeer::ENTITY_TYPE_ID, BaseEntityPeer::X, BaseEntityPeer::Y], 'required'],
            [[BaseEntityPeer::ENTITY_TYPE_ID, BaseEntityPeer::DURABILITY, BaseEntityPeer::X, BaseEntityPeer::Y], 'integer'],
            [[BaseEntityPeer::STATE], 'string'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseEntityPeer::ENTITY_ID => 'Entity ID',
            BaseEntityPeer::ENTITY_TYPE_ID => 'Entity Type ID',
            BaseEntityPeer::STATE => 'State',
            BaseEntityPeer::DURABILITY => 'Durability',
            BaseEntityPeer::X => 'X',
            BaseEntityPeer::Y => 'Y',
        ];
    }
    /**
     * @return \models\EntityCraftingQuery
     */
    public function getEntityCrafting() {
        return $this->hasOne(\models\EntityCrafting::className(), [BaseEntityCraftingPeer::ENTITY_ID => BaseEntityPeer::ENTITY_ID])->inverseOf('entity');
    }
        /**
     * @return \models\EntityResourceQuery
     */
    public function getEntityResources() {
        return $this->hasMany(\models\EntityResource::className(), [BaseEntityResourcePeer::ENTITY_ID => BaseEntityPeer::ENTITY_ID])->inverseOf('entity');
    }
        /**
     * @return \models\BaseResourceQuery
     */
    public function getResources() {
        return $this->hasMany(BaseResource::className(), [BaseResourcePeer::RESOURCE_ID => BaseEntityResourcePeer::RESOURCE_ID])->viaTable('entity_resource', [BaseEntityResourcePeer::ENTITY_ID => BaseEntityPeer::ENTITY_ID]);
    }
        /**
     * @return \models\EntityTransportQuery
     */
    public function getEntityTransport() {
        return $this->hasOne(\models\EntityTransport::className(), [BaseEntityTransportPeer::ENTITY_ID => BaseEntityPeer::ENTITY_ID])->inverseOf('entity');
    }
    
    /**
     * @inheritdoc
     * @return \models\EntityQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'entity_id' => BaseEntityPeer::ENTITY_ID,
            'entity_type_id' => BaseEntityPeer::ENTITY_TYPE_ID,
            'state' => BaseEntityPeer::STATE,
            'durability' => BaseEntityPeer::DURABILITY,
            'x' => BaseEntityPeer::X,
            'y' => BaseEntityPeer::Y,
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
            'entityCrafting' => 'entityCrafting',
            'entityResources' => 'entityResources',
            'resources' => 'resources',
            'entityTransport' => 'entityTransport',
        ];
        */
    }

}
