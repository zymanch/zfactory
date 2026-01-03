<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.technology_unlock_recipe".
 *
 * @property integer $technology_id
 * @property integer $recipe_id
 *
 * @property \models\Recipe $recipe
 * @property \models\Technology $technology
 */
class BaseTechnologyUnlockRecipe extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.technology_unlock_recipe';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID, BaseTechnologyUnlockRecipePeer::RECIPE_ID], 'required'],
            [[BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID, BaseTechnologyUnlockRecipePeer::RECIPE_ID], 'integer'],
            [[BaseTechnologyUnlockRecipePeer::RECIPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseRecipe::className(), 'targetAttribute' => [BaseTechnologyUnlockRecipePeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID]],
            [[BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseTechnology::className(), 'targetAttribute' => [BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID => 'Technology ID',
            BaseTechnologyUnlockRecipePeer::RECIPE_ID => 'Recipe ID',
        ];
    }
    /**
     * @return \models\RecipeQuery
     */
    public function getRecipe() {
        return $this->hasOne(\models\Recipe::className(), [BaseRecipePeer::RECIPE_ID => BaseTechnologyUnlockRecipePeer::RECIPE_ID]);
    }
        /**
     * @return \models\TechnologyQuery
     */
    public function getTechnology() {
        return $this->hasOne(\models\Technology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\TechnologyUnlockRecipeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\TechnologyUnlockRecipeQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'technology_id' => BaseTechnologyUnlockRecipePeer::TECHNOLOGY_ID,
            'recipe_id' => BaseTechnologyUnlockRecipePeer::RECIPE_ID,
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
            'recipe' => 'recipe',
            'technology' => 'technology',
        ];
        */
    }

}
