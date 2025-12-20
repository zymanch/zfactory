<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.resource".
 *
 * @property integer $resource_id
 * @property string $name
 * @property string $icon_url
 * @property string $type
 *
 * @property \models\EntityResource[] $entityResources
 * @property \models\BaseEntity[] $entities
 */
class BaseResource extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.resource';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseResourcePeer::NAME, BaseResourcePeer::ICON_URL], 'required'],
            [[BaseResourcePeer::TYPE], 'string'],
            [[BaseResourcePeer::NAME], 'string', 'max' => 128],
            [[BaseResourcePeer::ICON_URL], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseResourcePeer::RESOURCE_ID => 'Resource ID',
            BaseResourcePeer::NAME => 'Name',
            BaseResourcePeer::ICON_URL => 'Icon Url',
            BaseResourcePeer::TYPE => 'Type',
        ];
    }
    /**
     * @return \models\EntityResourceQuery
     */
    public function getEntityResources() {
        return $this->hasMany(\models\EntityResource::className(), [BaseEntityResourcePeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID])->inverseOf('resource');
    }
        /**
     * @return \models\BaseEntityQuery
     */
    public function getEntities() {
        return $this->hasMany(BaseEntity::className(), [BaseEntityPeer::ENTITY_ID => BaseEntityResourcePeer::ENTITY_ID])->viaTable('entity_resource', [BaseEntityResourcePeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\ResourceQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\ResourceQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'resource_id' => BaseResourcePeer::RESOURCE_ID,
            'name' => BaseResourcePeer::NAME,
            'icon_url' => BaseResourcePeer::ICON_URL,
            'type' => BaseResourcePeer::TYPE,
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
            'entityResources' => 'entityResources',
            'entities' => 'entities',
        ];
        */
    }

}
