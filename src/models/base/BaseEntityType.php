<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity_type".
 *
 * @property integer $entity_type_id
 * @property string $type
 * @property string $name
 * @property string $folder
 * @property string $extension
 * @property integer $max_durability
 * @property integer $converts_to_landing_id
 * @property integer $width
 * @property integer $height
 * @property string $icon_url
 * @property integer $power
 * @property integer $center_position_px
 * @property integer $parent_entity_type_id
 * @property string $orientation
 * @property string $animation_fps
 * @property string $description
 * @property integer $construction_ticks
 * @property string $storage_type
 * @property integer $storage_resource_count
 * @property integer $storage_per_resource
 * @property string $resource_types
 * @property string $input_connections
 * @property string $output_connections
 *
 * @property \models\Landing $convertsToLanding
 * @property \models\EntityTypeCost[] $entityTypeCosts
 * @property \models\BaseResource[] $resources
 * @property \models\EntityTypeRecipe[] $entityTypeRecipes
 * @property \models\BaseRecipe[] $recipes
 * @property \models\ShipEntity[] $shipEntities
 * @property \models\TechnologyUnlockEntityType[] $technologyUnlockEntityTypes
 * @property \models\BaseTechnology[] $technologies
 */
class BaseEntityType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.entity_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityTypePeer::ENTITY_TYPE_ID, BaseEntityTypePeer::TYPE, BaseEntityTypePeer::NAME, BaseEntityTypePeer::FOLDER], 'required'],
            [[BaseEntityTypePeer::ENTITY_TYPE_ID, BaseEntityTypePeer::MAX_DURABILITY, BaseEntityTypePeer::CONVERTS_TO_LANDING_ID, BaseEntityTypePeer::WIDTH, BaseEntityTypePeer::HEIGHT, BaseEntityTypePeer::POWER, BaseEntityTypePeer::CENTER_POSITION_PX, BaseEntityTypePeer::PARENT_ENTITY_TYPE_ID, BaseEntityTypePeer::CONSTRUCTION_TICKS, BaseEntityTypePeer::STORAGE_RESOURCE_COUNT, BaseEntityTypePeer::STORAGE_PER_RESOURCE], 'integer'],
            [[BaseEntityTypePeer::TYPE, BaseEntityTypePeer::ORIENTATION, BaseEntityTypePeer::DESCRIPTION, BaseEntityTypePeer::STORAGE_TYPE, BaseEntityTypePeer::RESOURCE_TYPES, BaseEntityTypePeer::INPUT_CONNECTIONS, BaseEntityTypePeer::OUTPUT_CONNECTIONS], 'string'],
            [[BaseEntityTypePeer::ANIMATION_FPS], 'number'],
            [[BaseEntityTypePeer::NAME], 'string', 'max' => 128],
            [[BaseEntityTypePeer::FOLDER, BaseEntityTypePeer::ICON_URL], 'string', 'max' => 256],
            [[BaseEntityTypePeer::EXTENSION], 'string', 'max' => 4],
            [[BaseEntityTypePeer::CONVERTS_TO_LANDING_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseLanding::className(), 'targetAttribute' => [BaseEntityTypePeer::CONVERTS_TO_LANDING_ID => BaseLandingPeer::LANDING_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseEntityTypePeer::ENTITY_TYPE_ID => 'Entity Type ID',
            BaseEntityTypePeer::TYPE => 'Type',
            BaseEntityTypePeer::NAME => 'Name',
            BaseEntityTypePeer::FOLDER => 'Folder',
            BaseEntityTypePeer::EXTENSION => 'Extension',
            BaseEntityTypePeer::MAX_DURABILITY => 'Max Durability',
            BaseEntityTypePeer::CONVERTS_TO_LANDING_ID => 'Converts To Landing ID',
            BaseEntityTypePeer::WIDTH => 'Width',
            BaseEntityTypePeer::HEIGHT => 'Height',
            BaseEntityTypePeer::ICON_URL => 'Icon Url',
            BaseEntityTypePeer::POWER => 'Power',
            BaseEntityTypePeer::CENTER_POSITION_PX => 'Center Position Px',
            BaseEntityTypePeer::PARENT_ENTITY_TYPE_ID => 'Parent Entity Type ID',
            BaseEntityTypePeer::ORIENTATION => 'Orientation',
            BaseEntityTypePeer::ANIMATION_FPS => 'Animation Fps',
            BaseEntityTypePeer::DESCRIPTION => 'Description',
            BaseEntityTypePeer::CONSTRUCTION_TICKS => 'Construction Ticks',
            BaseEntityTypePeer::STORAGE_TYPE => 'Storage Type',
            BaseEntityTypePeer::STORAGE_RESOURCE_COUNT => 'Storage Resource Count',
            BaseEntityTypePeer::STORAGE_PER_RESOURCE => 'Storage Per Resource',
            BaseEntityTypePeer::RESOURCE_TYPES => 'Resource Types',
            BaseEntityTypePeer::INPUT_CONNECTIONS => 'Input Connections',
            BaseEntityTypePeer::OUTPUT_CONNECTIONS => 'Output Connections',
        ];
    }
    /**
     * @return \models\LandingQuery
     */
    public function getConvertsToLanding() {
        return $this->hasOne(\models\Landing::className(), [BaseLandingPeer::LANDING_ID => BaseEntityTypePeer::CONVERTS_TO_LANDING_ID]);
    }
        /**
     * @return \models\EntityTypeCostQuery
     */
    public function getEntityTypeCosts() {
        return $this->hasMany(\models\EntityTypeCost::className(), [BaseEntityTypeCostPeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID])->inverseOf('entityType');
    }
        /**
     * @return \models\BaseResourceQuery
     */
    public function getResources() {
        return $this->hasMany(BaseResource::className(), [BaseResourcePeer::RESOURCE_ID => BaseEntityTypeCostPeer::RESOURCE_ID])->viaTable('entity_type_cost', [BaseEntityTypeCostPeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID]);
    }
        /**
     * @return \models\EntityTypeRecipeQuery
     */
    public function getEntityTypeRecipes() {
        return $this->hasMany(\models\EntityTypeRecipe::className(), [BaseEntityTypeRecipePeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID])->inverseOf('entityType');
    }
        /**
     * @return \models\BaseRecipeQuery
     */
    public function getRecipes() {
        return $this->hasMany(BaseRecipe::className(), [BaseRecipePeer::RECIPE_ID => BaseEntityTypeRecipePeer::RECIPE_ID])->viaTable('entity_type_recipe', [BaseEntityTypeRecipePeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID]);
    }
        /**
     * @return \models\ShipEntityQuery
     */
    public function getShipEntities() {
        return $this->hasMany(\models\ShipEntity::className(), [BaseShipEntityPeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID])->inverseOf('entityType');
    }
        /**
     * @return \models\TechnologyUnlockEntityTypeQuery
     */
    public function getTechnologyUnlockEntityTypes() {
        return $this->hasMany(\models\TechnologyUnlockEntityType::className(), [BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID])->inverseOf('entityType');
    }
        /**
     * @return \models\BaseTechnologyQuery
     */
    public function getTechnologies() {
        return $this->hasMany(BaseTechnology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID])->viaTable('technology_unlock_entity_type', [BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\EntityTypeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityTypeQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'entity_type_id' => BaseEntityTypePeer::ENTITY_TYPE_ID,
            'type' => BaseEntityTypePeer::TYPE,
            'name' => BaseEntityTypePeer::NAME,
            'folder' => BaseEntityTypePeer::FOLDER,
            'extension' => BaseEntityTypePeer::EXTENSION,
            'max_durability' => BaseEntityTypePeer::MAX_DURABILITY,
            'converts_to_landing_id' => BaseEntityTypePeer::CONVERTS_TO_LANDING_ID,
            'width' => BaseEntityTypePeer::WIDTH,
            'height' => BaseEntityTypePeer::HEIGHT,
            'icon_url' => BaseEntityTypePeer::ICON_URL,
            'power' => BaseEntityTypePeer::POWER,
            'center_position_px' => BaseEntityTypePeer::CENTER_POSITION_PX,
            'parent_entity_type_id' => BaseEntityTypePeer::PARENT_ENTITY_TYPE_ID,
            'orientation' => BaseEntityTypePeer::ORIENTATION,
            'animation_fps' => BaseEntityTypePeer::ANIMATION_FPS,
            'description' => BaseEntityTypePeer::DESCRIPTION,
            'construction_ticks' => BaseEntityTypePeer::CONSTRUCTION_TICKS,
            'storage_type' => BaseEntityTypePeer::STORAGE_TYPE,
            'storage_resource_count' => BaseEntityTypePeer::STORAGE_RESOURCE_COUNT,
            'storage_per_resource' => BaseEntityTypePeer::STORAGE_PER_RESOURCE,
            'resource_types' => BaseEntityTypePeer::RESOURCE_TYPES,
            'input_connections' => BaseEntityTypePeer::INPUT_CONNECTIONS,
            'output_connections' => BaseEntityTypePeer::OUTPUT_CONNECTIONS,
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
            'convertsToLanding' => 'convertsToLanding',
            'entityTypeCosts' => 'entityTypeCosts',
            'resources' => 'resources',
            'entityTypeRecipes' => 'entityTypeRecipes',
            'recipes' => 'recipes',
            'shipEntities' => 'shipEntities',
            'technologyUnlockEntityTypes' => 'technologyUnlockEntityTypes',
            'technologies' => 'technologies',
        ];
        */
    }

}
