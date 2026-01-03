<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.technology_dependency".
 *
 * @property integer $technology_id
 * @property integer $required_technology_id
 *
 * @property \models\Technology $requiredTechnology
 * @property \models\Technology $technology
 */
class BaseTechnologyDependency extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.technology_dependency';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseTechnologyDependencyPeer::TECHNOLOGY_ID, BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID], 'required'],
            [[BaseTechnologyDependencyPeer::TECHNOLOGY_ID, BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID], 'integer'],
            [[BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseTechnology::className(), 'targetAttribute' => [BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]],
            [[BaseTechnologyDependencyPeer::TECHNOLOGY_ID], 'exist', 'skipOnError' => true, 'targetClass' => BaseTechnology::className(), 'targetAttribute' => [BaseTechnologyDependencyPeer::TECHNOLOGY_ID => BaseTechnologyPeer::TECHNOLOGY_ID]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseTechnologyDependencyPeer::TECHNOLOGY_ID => 'Technology ID',
            BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID => 'Required Technology ID',
        ];
    }
    /**
     * @return \models\TechnologyQuery
     */
    public function getRequiredTechnology() {
        return $this->hasOne(\models\Technology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID]);
    }
        /**
     * @return \models\TechnologyQuery
     */
    public function getTechnology() {
        return $this->hasOne(\models\Technology::className(), [BaseTechnologyPeer::TECHNOLOGY_ID => BaseTechnologyDependencyPeer::TECHNOLOGY_ID]);
    }
    
    /**
     * @inheritdoc
     * @return \models\TechnologyDependencyQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\TechnologyDependencyQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'technology_id' => BaseTechnologyDependencyPeer::TECHNOLOGY_ID,
            'required_technology_id' => BaseTechnologyDependencyPeer::REQUIRED_TECHNOLOGY_ID,
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
            'requiredTechnology' => 'requiredTechnology',
            'technology' => 'technology',
        ];
        */
    }

}
