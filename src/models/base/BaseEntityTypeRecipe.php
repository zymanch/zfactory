<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity_type_recipe".
 *
 * @property integer $entity_type_id
 * @property integer $recipe_id
 *
 * @property \models\EntityType $entityType
 * @property \models\Recipe $recipe
 */
class BaseEntityTypeRecipe extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.entity_type_recipe';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityTypeRecipePeer::ENTITY_TYPE_ID, BaseEntityTypeRecipePeer::RECIPE_ID], 'required'],
            [[BaseEntityTypeRecipePeer::ENTITY_TYPE_ID, BaseEntityTypeRecipePeer::RECIPE_ID], 'integer'],
            [[BaseEntityTypeRecipePeer::ENTITY_TYPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseEntityType::className(), 'targetAttribute' => [BaseEntityTypeRecipePeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID]],
            [[BaseEntityTypeRecipePeer::RECIPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseRecipe::className(), 'targetAttribute' => [BaseEntityTypeRecipePeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseEntityTypeRecipePeer::ENTITY_TYPE_ID => 'Entity Type ID',
            BaseEntityTypeRecipePeer::RECIPE_ID => 'Recipe ID',
        ];
    }
    /**
     * @return \models\EntityTypeQuery
     */
    public function getEntityType() {
        return $this->hasOne(\models\EntityType::className(), [BaseEntityTypePeer::ENTITY_TYPE_ID => BaseEntityTypeRecipePeer::ENTITY_TYPE_ID]);
    }
        /**
     * @return \models\RecipeQuery
     */
    public function getRecipe() {
        return $this->hasOne(\models\Recipe::className(), [BaseRecipePeer::RECIPE_ID => BaseEntityTypeRecipePeer::RECIPE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\EntityTypeRecipeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityTypeRecipeQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'entity_type_id' => BaseEntityTypeRecipePeer::ENTITY_TYPE_ID,
            'recipe_id' => BaseEntityTypeRecipePeer::RECIPE_ID,
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
            'recipe' => 'recipe',
        ];
        */
    }

}
