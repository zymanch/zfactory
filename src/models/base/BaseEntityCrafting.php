<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity_crafting".
 *
 * @property integer $entity_id
 * @property integer $recipe_id
 * @property integer $ticks_remaining
 *
 * @property \models\Entity $entity
 * @property \models\Recipe $recipe
 */
class BaseEntityCrafting extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.entity_crafting';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseEntityCraftingPeer::ENTITY_ID, BaseEntityCraftingPeer::RECIPE_ID, BaseEntityCraftingPeer::TICKS_REMAINING], 'required'],
            [[BaseEntityCraftingPeer::ENTITY_ID, BaseEntityCraftingPeer::RECIPE_ID, BaseEntityCraftingPeer::TICKS_REMAINING], 'integer'],
            [[BaseEntityCraftingPeer::ENTITY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseEntity::className(), 'targetAttribute' => [BaseEntityCraftingPeer::ENTITY_ID => BaseEntityPeer::ENTITY_ID]],
            [[BaseEntityCraftingPeer::RECIPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseRecipe::className(), 'targetAttribute' => [BaseEntityCraftingPeer::RECIPE_ID => BaseRecipePeer::RECIPE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseEntityCraftingPeer::ENTITY_ID => 'Entity ID',
            BaseEntityCraftingPeer::RECIPE_ID => 'Recipe ID',
            BaseEntityCraftingPeer::TICKS_REMAINING => 'Ticks Remaining',
        ];
    }
    /**
     * @return \models\EntityQuery
     */
    public function getEntity() {
        return $this->hasOne(\models\Entity::className(), [BaseEntityPeer::ENTITY_ID => BaseEntityCraftingPeer::ENTITY_ID]);
    }
        /**
     * @return \models\RecipeQuery
     */
    public function getRecipe() {
        return $this->hasOne(\models\Recipe::className(), [BaseRecipePeer::RECIPE_ID => BaseEntityCraftingPeer::RECIPE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\EntityCraftingQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\EntityCraftingQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'entity_id' => BaseEntityCraftingPeer::ENTITY_ID,
            'recipe_id' => BaseEntityCraftingPeer::RECIPE_ID,
            'ticks_remaining' => BaseEntityCraftingPeer::TICKS_REMAINING,
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
            'entity' => 'entity',
            'recipe' => 'recipe',
        ];
        */
    }

}
