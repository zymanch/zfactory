<?php

namespace models\base;

/**
 * This is the model class for table "recipe".
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
 */
class BaseRecipe extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'recipe';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseRecipePeer::OUTPUT_RESOURCE_ID, BaseRecipePeer::INPUT1_RESOURCE_ID], 'required'],
            [[BaseRecipePeer::OUTPUT_RESOURCE_ID, BaseRecipePeer::OUTPUT_AMOUNT, BaseRecipePeer::INPUT1_RESOURCE_ID, BaseRecipePeer::INPUT1_AMOUNT, BaseRecipePeer::INPUT2_RESOURCE_ID, BaseRecipePeer::INPUT2_AMOUNT, BaseRecipePeer::INPUT3_RESOURCE_ID, BaseRecipePeer::INPUT3_AMOUNT, BaseRecipePeer::TICKS], 'integer'],
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
            BaseRecipePeer::INPUT1_RESOURCE_ID => 'Input 1 Resource ID',
            BaseRecipePeer::INPUT1_AMOUNT => 'Input 1 Amount',
            BaseRecipePeer::INPUT2_RESOURCE_ID => 'Input 2 Resource ID',
            BaseRecipePeer::INPUT2_AMOUNT => 'Input 2 Amount',
            BaseRecipePeer::INPUT3_RESOURCE_ID => 'Input 3 Resource ID',
            BaseRecipePeer::INPUT3_AMOUNT => 'Input 3 Amount',
            BaseRecipePeer::TICKS => 'Ticks',
        ];
    }

    /**
     * @inheritdoc
     * @return \models\RecipeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\RecipeQuery(get_called_class());
    }
}
