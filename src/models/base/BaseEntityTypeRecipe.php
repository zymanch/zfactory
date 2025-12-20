<?php

namespace models\base;

/**
 * This is the model class for table "entity_type_recipe".
 *
 * @property integer $entity_type_id
 * @property integer $recipe_id
 */
class BaseEntityTypeRecipe extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'entity_type_recipe';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityTypeRecipePeer::ENTITY_TYPE_ID, BaseEntityTypeRecipePeer::RECIPE_ID], 'required'],
            [[BaseEntityTypeRecipePeer::ENTITY_TYPE_ID, BaseEntityTypeRecipePeer::RECIPE_ID], 'integer'],
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
     * @inheritdoc
     * @return \models\EntityTypeRecipeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityTypeRecipeQuery(get_called_class());
    }
}
