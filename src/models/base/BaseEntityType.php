<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.entity_type".
 *
 * @property integer $entity_type_id
 * @property string $type
 * @property string $name
 * @property string $image_url
 * @property string $extension
 * @property integer $max_durability
 * @property integer $width
 * @property integer $height
 * @property string $icon_url
 * @property integer $power
 * @property integer $parent_entity_type_id
 * @property string $orientation
 * @property string $animation_fps
 *
 * @property \models\EntityTypeRecipe[] $entityTypeRecipes
 * @property \models\BaseRecipe[] $recipes
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
            [[BaseEntityTypePeer::ENTITY_TYPE_ID, BaseEntityTypePeer::TYPE, BaseEntityTypePeer::NAME, BaseEntityTypePeer::IMAGE_URL], 'required'],
            [[BaseEntityTypePeer::ENTITY_TYPE_ID, BaseEntityTypePeer::MAX_DURABILITY, BaseEntityTypePeer::WIDTH, BaseEntityTypePeer::HEIGHT, BaseEntityTypePeer::POWER, BaseEntityTypePeer::PARENT_ENTITY_TYPE_ID], 'integer'],
            [[BaseEntityTypePeer::TYPE, BaseEntityTypePeer::ORIENTATION], 'string'],
            [[BaseEntityTypePeer::ANIMATION_FPS], 'number'],
            [[BaseEntityTypePeer::NAME], 'string', 'max' => 128],
            [[BaseEntityTypePeer::IMAGE_URL, BaseEntityTypePeer::ICON_URL], 'string', 'max' => 256],
            [[BaseEntityTypePeer::EXTENSION], 'string', 'max' => 4],
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
            BaseEntityTypePeer::IMAGE_URL => 'Image Url',
            BaseEntityTypePeer::EXTENSION => 'Extension',
            BaseEntityTypePeer::MAX_DURABILITY => 'Max Durability',
            BaseEntityTypePeer::WIDTH => 'Width',
            BaseEntityTypePeer::HEIGHT => 'Height',
            BaseEntityTypePeer::ICON_URL => 'Icon Url',
            BaseEntityTypePeer::POWER => 'Power',
            BaseEntityTypePeer::PARENT_ENTITY_TYPE_ID => 'Parent Entity Type ID',
            BaseEntityTypePeer::ORIENTATION => 'Orientation',
            BaseEntityTypePeer::ANIMATION_FPS => 'Animation Fps',
        ];
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
            'image_url' => BaseEntityTypePeer::IMAGE_URL,
            'extension' => BaseEntityTypePeer::EXTENSION,
            'max_durability' => BaseEntityTypePeer::MAX_DURABILITY,
            'width' => BaseEntityTypePeer::WIDTH,
            'height' => BaseEntityTypePeer::HEIGHT,
            'icon_url' => BaseEntityTypePeer::ICON_URL,
            'power' => BaseEntityTypePeer::POWER,
            'parent_entity_type_id' => BaseEntityTypePeer::PARENT_ENTITY_TYPE_ID,
            'orientation' => BaseEntityTypePeer::ORIENTATION,
            'animation_fps' => BaseEntityTypePeer::ANIMATION_FPS,
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
            'entityTypeRecipes' => 'entityTypeRecipes',
            'recipes' => 'recipes',
        ];
        */
    }

}
