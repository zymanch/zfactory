<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.technology_unlock_entity_type".
 *
 * @property integer $technology_id
 * @property integer $entity_type_id
 *
 * @property \models\Technology $technology
 * @property \models\EntityType $entityType
 */
class BaseTechnologyUnlockEntityType extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.technology_unlock_entity_type';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID, BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID], 'required'],
            [[BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID, BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID], 'integer'],
            [[BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseTechnology::className(), 'targetAttribute' => [BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]],
            [[BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseEntityType::className(), 'targetAttribute' => [BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID => BaseEntityTypePeer::ENTITY_TYPE_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID => 'Technology ID',
            BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID => 'Entity Type ID',
        ];
    }
    /**
     * @return \models\TechnologyQuery
     */
    public function getTechnology() {
        return $this->hasOne(\models\Technology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID]);
    }
        /**
     * @return \models\EntityTypeQuery
     */
    public function getEntityType() {
        return $this->hasOne(\models\EntityType::className(), [BaseEntityTypePeer::ENTITY_TYPE_ID => BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\TechnologyUnlockEntityTypeQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\TechnologyUnlockEntityTypeQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'technology_id' => BaseTechnologyUnlockEntityTypePeer::TECHNOLOGY_ID,
            'entity_type_id' => BaseTechnologyUnlockEntityTypePeer::ENTITY_TYPE_ID,
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
            'technology' => 'technology',
            'entityType' => 'entityType',
        ];
        */
    }

}
