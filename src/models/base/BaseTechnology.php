<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.technology".
 *
 * @property integer $technology_id
 * @property string $name
 * @property string $description
 * @property string $icon
 * @property integer $tier
 *
 * @property \models\TechnologyCost[] $technologyCosts
 * @property \models\BaseResource[] $resources
 * @property \models\TechnologyDependency[] $technologyDependencies
 * @property \models\TechnologyDependency[] $technologyDependencies0
 * @property \models\BaseTechnology[] $technologies
 * @property \models\BaseTechnology[] $requiredTechnologies
 * @property \models\TechnologyUnlockEntityType[] $technologyUnlockEntityTypes
 * @property \models\BaseEntityType[] $entityTypes
 * @property \models\TechnologyUnlockRecipe[] $technologyUnlockRecipes
 * @property \models\BaseRecipe[] $recipes
 * @property \models\UserTechnology[] $userTechnologies
 * @property \models\BaseUser[] $users
 */
class BaseTechnology extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.technology';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseTechnologyPeer::NAME], 'required'],
            [[BaseTechnologyPeer::DESCRIPTION], 'string'],
            [[BaseTechnologyPeer::TIER], 'integer'],
            [[BaseTechnologyPeer::NAME], 'string', 'max' => 128],
            [[BaseTechnologyPeer::ICON], 'string', 'max' => 256],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseTechnologyPeer::TECHNOLOGY_ID => 'Technology ID',
            BaseTechnologyPeer::NAME => 'Name',
            BaseTechnologyPeer::DESCRIPTION => 'Description',
            BaseTechnologyPeer::ICON => 'Icon',
            BaseTechnologyPeer::TIER => 'Tier',
        ];
    }
    /**
     * @return \models\TechnologyCostQuery
     */
    public function getTechnologyCosts() {
        return $this->hasMany(\models\TechnologyCost::className(), [BaseTechnologyCostPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID])->inverseOf('technology');
    }
        /**
     * @return \models\BaseResourceQuery
     */
    public function getResources() {
        return $this->hasMany(BaseResource::className(), [BaseResourcePeer::RESOURCE_ID => BaseTechnologyCostPeer::RESOURCE_ID])->viaTable('technology_cost', [BaseTechnologyCostPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]);
    }
        /**
     * @return \models\TechnologyDependencyQuery
     */
    public function getTechnologyDependencies() {
        return $this->hasMany(\models\TechnologyDependency::className(), [BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID])->inverseOf('requiredTechnology');
    }
        /**
     * @return \models\TechnologyDependencyQuery
     */
    public function getTechnologyDependencies0() {
        return $this->hasMany(\models\TechnologyDependency::className(), [BaseTechnologyDependencyPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID])->inverseOf('technology');
    }
        /**
     * @return \models\BaseTechnologyQuery
     */
    public function getTechnologies() {
        return $this->hasMany(BaseTechnology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyDependencyPeer::TECHNOLOGY_ID])->viaTable('technology_dependency', [BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]);
    }
        /**
     * @return \models\BaseTechnologyQuery
     */
    public function getRequiredTechnologies() {
        return $this->hasMany(BaseTechnology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID])->viaTable('technology_dependency', [BaseTechnologyDependencyPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]);
    }
        /**
     * @return \models\TechnologyUnlockEntityTypeQuery
     */
    public function getTechnologyUnlockEntityTypes() {
        return $this->hasMany(\models\TechnologyUnlockEntityType::className(), [BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID])->inverseOf('technology');
    }
        /**
     * @return \models\BaseEntityTypeQuery
     */
    public function getEntityTypes() {
        return $this->hasMany(BaseEntityType::className(), [BaseEntityTypePeer::ENTITY_TYPE_ID => BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID])->viaTable('technology_unlock_entity_type', [BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]);
    }
        /**
     * @return \models\TechnologyUnlockRecipeQuery
     */
    public function getTechnologyUnlockRecipes() {
        return $this->hasMany(\models\TechnologyUnlockRecipe::className(), [BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID])->inverseOf('technology');
    }
        /**
     * @return \models\BaseRecipeQuery
     */
    public function getRecipes() {
        return $this->hasMany(BaseRecipe::className(), [BaseRecipePeer::RECIPE_ID => BaseTechnologyUnlockRecipePeer::RECIPE_ID])->viaTable('technology_unlock_recipe', [BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]);
    }
        /**
     * @return \models\UserTechnologyQuery
     */
    public function getUserTechnologies() {
        return $this->hasMany(\models\UserTechnology::className(), [BaseUserTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID])->inverseOf('technology');
    }
        /**
     * @return \models\BaseUserQuery
     */
    public function getUsers() {
        return $this->hasMany(BaseUser::className(), [BaseUserPeer::USER_ID => BaseUserTechnologyPeer::USER_ID])->viaTable('user_technology', [BaseUserTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\TechnologyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\TechnologyQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'technology_id' => BaseTechnologyPeer::TECHNOLOGY_ID,
            'name' => BaseTechnologyPeer::NAME,
            'description' => BaseTechnologyPeer::DESCRIPTION,
            'icon' => BaseTechnologyPeer::ICON,
            'tier' => BaseTechnologyPeer::TIER,
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
            'technologyCosts' => 'technologyCosts',
            'resources' => 'resources',
            'technologyDependencies' => 'technologyDependencies',
            'technologyDependencies0' => 'technologyDependencies0',
            'technologies' => 'technologies',
            'requiredTechnologies' => 'requiredTechnologies',
            'technologyUnlockEntityTypes' => 'technologyUnlockEntityTypes',
            'entityTypes' => 'entityTypes',
            'technologyUnlockRecipes' => 'technologyUnlockRecipes',
            'recipes' => 'recipes',
            'userTechnologies' => 'userTechnologies',
            'users' => 'users',
        ];
        */
    }

}
