<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.resource".
 *
 * @property integer $resource_id
 * @property string $name
 * @property string $icon_url
 * @property string $type
 * @property integer $max_stack
 *
 * @property \models\EntityResource[] $entityResources
 * @property \models\BaseEntity[] $entities
 * @property \models\EntityTransport[] $entityTransports
 * @property \models\Recipe[] $recipes
 * @property \models\Recipe[] $recipes0
 * @property \models\Recipe[] $recipes1
 * @property \models\Recipe[] $recipes2
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
            [[BaseResourcePeer::NAME, BaseResourcePeer::ICON_URL, BaseResourcePeer::TYPE], 'required'],
            [[BaseResourcePeer::TYPE], 'string'],
            [[BaseResourcePeer::MAX_STACK], 'integer'],
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
            BaseResourcePeer::MAX_STACK => 'Max Stack',
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
     * @return \models\EntityTransportQuery
     */
    public function getEntityTransports() {
        return $this->hasMany(\models\EntityTransport::className(), [BaseEntityTransportPeer::RESOURCE_ID => BaseResourcePeer::RESOURCE_ID])->inverseOf('resource');
    }
        /**
     * @return \models\RecipeQuery
     */
    public function getRecipes() {
        return $this->hasMany(\models\Recipe::className(), [BaseRecipePeer::INPUT1_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID])->inverseOf('input1Resource');
    }
        /**
     * @return \models\RecipeQuery
     */
    public function getRecipes0() {
        return $this->hasMany(\models\Recipe::className(), [BaseRecipePeer::INPUT2_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID])->inverseOf('input2Resource');
    }
        /**
     * @return \models\RecipeQuery
     */
    public function getRecipes1() {
        return $this->hasMany(\models\Recipe::className(), [BaseRecipePeer::INPUT3_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID])->inverseOf('input3Resource');
    }
        /**
     * @return \models\RecipeQuery
     */
    public function getRecipes2() {
        return $this->hasMany(\models\Recipe::className(), [BaseRecipePeer::OUTPUT_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID])->inverseOf('outputResource');
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
            'max_stack' => BaseResourcePeer::MAX_STACK,
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
            'entityTransports' => 'entityTransports',
            'recipes' => 'recipes',
            'recipes0' => 'recipes0',
            'recipes1' => 'recipes1',
            'recipes2' => 'recipes2',
        ];
        */
    }

}
