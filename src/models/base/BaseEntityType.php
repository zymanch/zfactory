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
            [[BaseEntityTypePeer::ENTITY_TYPE_ID, BaseEntityTypePeer::MAX_DURABILITY, BaseEntityTypePeer::WIDTH, BaseEntityTypePeer::HEIGHT, BaseEntityTypePeer::POWER], 'integer'],
            [[BaseEntityTypePeer::TYPE], 'string'],
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
        ];
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
            ,
        ];
        */
    }

}
