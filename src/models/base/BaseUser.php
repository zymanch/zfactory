<?php

namespace models\base;



/**
 * This is the model class for table "zfactory.user".
 *
 * @property integer $user_id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string $build_panel
 * @property integer $camera_x
 * @property integer $camera_y
 * @property double $zoom
 * @property string $created_at
 * @property string $updated_at
 */
class BaseUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'zfactory.user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [[BaseUserPeer::USERNAME, BaseUserPeer::PASSWORD, BaseUserPeer::EMAIL], 'required'],
            [[BaseUserPeer::BUILD_PANEL], 'string'],
            [[BaseUserPeer::CAMERA_X, BaseUserPeer::CAMERA_Y], 'integer'],
            [[BaseUserPeer::ZOOM], 'number'],
            [[BaseUserPeer::CREATED_AT, BaseUserPeer::UPDATED_AT], 'safe'],
            [[BaseUserPeer::USERNAME], 'string', 'max' => 64],
            [[BaseUserPeer::PASSWORD], 'string', 'max' => 255],
            [[BaseUserPeer::EMAIL], 'string', 'max' => 128],
            [[BaseUserPeer::USERNAME], 'unique'],
            [[BaseUserPeer::EMAIL], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            BaseUserPeer::USER_ID => 'User ID',
            BaseUserPeer::USERNAME => 'Username',
            BaseUserPeer::PASSWORD => 'Password',
            BaseUserPeer::EMAIL => 'Email',
            BaseUserPeer::BUILD_PANEL => 'Build Panel',
            BaseUserPeer::CAMERA_X => 'Camera X',
            BaseUserPeer::CAMERA_Y => 'Camera Y',
            BaseUserPeer::ZOOM => 'Zoom',
            BaseUserPeer::CREATED_AT => 'Created At',
            BaseUserPeer::UPDATED_AT => 'Updated At',
        ];
    }

    /**
     * @inheritdoc
     * @return \models\UserQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new \models\UserQuery(get_called_class());
    }

    
    /**
    * @inheritdoc
    * @return array of columns available for rest query
    */
    public function getRestColumns()
    {
        return [
            'user_id' => BaseUserPeer::USER_ID,
            'username' => BaseUserPeer::USERNAME,
            'password' => BaseUserPeer::PASSWORD,
            'email' => BaseUserPeer::EMAIL,
            'build_panel' => BaseUserPeer::BUILD_PANEL,
            'camera_x' => BaseUserPeer::CAMERA_X,
            'camera_y' => BaseUserPeer::CAMERA_Y,
            'zoom' => BaseUserPeer::ZOOM,
            'created_at' => BaseUserPeer::CREATED_AT,
            'updated_at' => BaseUserPeer::UPDATED_AT,
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
