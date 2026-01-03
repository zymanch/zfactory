<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.recipe".
 *
 * @property integer $recipe_id
 * @property integer $output_resource_id
 * @property integer $output_amount
 * @property integer $input1_resource_id
 * @property integer $input1_amount
 * @property integer $input2_resource_id
 * @property integer $input2_amount
 * @property integer $input3_resource_id
 * @property integer $input3_amount
 * @property integer $ticks
 *
 * @property \models\EntityCrafting[] $entityCraftings
 * @property \models\EntityTypeRecipe[] $entityTypeRecipes
 * @property \models\BaseEntityType[] $entityTypes
 * @property \models\Resource $input1Resource
 * @property \models\Resource $input2Resource
 * @property \models\Resource $input3Resource
 * @property \models\Resource $outputResource
 * @property \models\TechnologyUnlockRecipe[] $technologyUnlockRecipes
 * @property \models\BaseTechnology[] $technologies
 */
class BaseRecipe extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.recipe';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseRecipePeer::OUTPUT_RESOURCE_ID, BaseRecipePeer::INPUT1_RESOURCE_ID], 'required'],
            [[BaseRecipePeer::OUTPUT_RESOURCE_ID, BaseRecipePeer::OUTPUT_AMOUNT, BaseRecipePeer::INPUT1_RESOURCE_ID, BaseRecipePeer::INPUT1_AMOUNT, BaseRecipePeer::INPUT2_RESOURCE_ID, BaseRecipePeer::INPUT2_AMOUNT, BaseRecipePeer::INPUT3_RESOURCE_ID, BaseRecipePeer::INPUT3_AMOUNT, BaseRecipePeer::TICKS], 'integer'],
            [[BaseRecipePeer::INPUT1_RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseRecipePeer::INPUT1_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
            [[BaseRecipePeer::INPUT2_RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseRecipePeer::INPUT2_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
            [[BaseRecipePeer::INPUT3_RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseRecipePeer::INPUT3_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
            [[BaseRecipePeer::OUTPUT_RESOURCE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseResource::className(), 'targetAttribute' => [BaseRecipePeer::OUTPUT_RESOURCE_ID => BaseResourcePeer::RESOURCE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseRecipePeer::RECIPE_ID => 'Recipe ID',
            BaseRecipePeer::OUTPUT_RESOURCE_ID => 'Output Resource ID',
            BaseRecipePeer::OUTPUT_AMOUNT => 'Output Amount',
            BaseRecipePeer::INPUT1_RESOURCE_ID => 'Input1 Resource ID',
            BaseRecipePeer::INPUT1_AMOUNT => 'Input1 Amount',
            BaseRecipePeer::INPUT2_RESOURCE_ID => 'Input2 Resource ID',
            BaseRecipePeer::INPUT2_AMOUNT => 'Input2 Amount',
            BaseRecipePeer::INPUT3_RESOURCE_ID => 'Input3 Resource ID',
            BaseRecipePeer::INPUT3_AMOUNT => 'Input3 Amount',
            BaseRecipePeer::TICKS => 'Ticks',
        ];
    }
    /**
     * @return \models\EntityCraftingQuery
     */
    public function getEntityCraftings() {
        return $this->hasMany(\models\EntityCrafting::className(), [BaseEntityCraftingPeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID])->inverseOf('recipe');
    }
        /**
     * @return \models\EntityTypeRecipeQuery
     */
    public function getEntityTypeRecipes() {
        return $this->hasMany(\models\EntityTypeRecipe::className(), [BaseEntityTypeRecipePeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID])->inverseOf('recipe');
    }
        /**
     * @return \models\BaseEntityTypeQuery
     */
    public function getEntityTypes() {
        return $this->hasMany(BaseEntityType::className(), [BaseEntityTypePeer::ENTITY_TYPE_ID => BaseEntityTypeRecipePeer::ENTITY_TYPE_ID])->viaTable('entity_type_recipe', [BaseEntityTypeRecipePeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID]);
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getInput1Resource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseRecipePeer::INPUT1_RESOURCE_ID]);
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getInput2Resource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseRecipePeer::INPUT2_RESOURCE_ID]);
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getInput3Resource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseRecipePeer::INPUT3_RESOURCE_ID]);
    }
        /**
     * @return \models\ResourceQuery
     */
    public function getOutputResource() {
        return $this->hasOne(\models\Resource::className(), [BaseResourcePeer::RESOURCE_ID => BaseRecipePeer::OUTPUT_RESOURCE_ID]);
    }
        /**
     * @return \models\TechnologyUnlockRecipeQuery
     */
    public function getTechnologyUnlockRecipes() {
        return $this->hasMany(\models\TechnologyUnlockRecipe::className(), [BaseTechnologyUnlockRecipePeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID])->inverseOf('recipe');
    }
        /**
     * @return \models\BaseTechnologyQuery
     */
    public function getTechnologies() {
        return $this->hasMany(BaseTechnology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID])->viaTable('technology_unlock_recipe', [BaseTechnologyUnlockRecipePeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\RecipeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\RecipeQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'recipe_id' => BaseRecipePeer::RECIPE_ID,
            'output_resource_id' => BaseRecipePeer::OUTPUT_RESOURCE_ID,
            'output_amount' => BaseRecipePeer::OUTPUT_AMOUNT,
            'input1_resource_id' => BaseRecipePeer::INPUT1_RESOURCE_ID,
            'input1_amount' => BaseRecipePeer::INPUT1_AMOUNT,
            'input2_resource_id' => BaseRecipePeer::INPUT2_RESOURCE_ID,
            'input2_amount' => BaseRecipePeer::INPUT2_AMOUNT,
            'input3_resource_id' => BaseRecipePeer::INPUT3_RESOURCE_ID,
            'input3_amount' => BaseRecipePeer::INPUT3_AMOUNT,
            'ticks' => BaseRecipePeer::TICKS,
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
            'entityCraftings' => 'entityCraftings',
            'entityTypeRecipes' => 'entityTypeRecipes',
            'entityTypes' => 'entityTypes',
            'input1Resource' => 'input1Resource',
            'input2Resource' => 'input2Resource',
            'input3Resource' => 'input3Resource',
            'outputResource' => 'outputResource',
            'technologyUnlockRecipes' => 'technologyUnlockRecipes',
            'technologies' => 'technologies',
        ];
        */
    }

}
