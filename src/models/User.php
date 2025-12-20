<?php

namespace models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property int $user_id
 * @property string $username
 * @property string $password
 * @property string $email
 * @property string|null $build_panel JSON array of entity_type_ids
 * @property int $camera_x Camera X position
 * @property int $camera_y Camera Y position
 * @property float $zoom Camera zoom level
 * @property string $created_at
 * @property string $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'password', 'email'], 'required'],
            [['username'], 'string', 'max' => 64],
            [['password'], 'string', 'max' => 255],
            [['email'], 'string', 'max' => 128],
            [['email'], 'email'],
            [['username', 'email'], 'unique'],
            [['build_panel'], 'safe'],
            [['camera_x', 'camera_y'], 'integer'],
            [['zoom'], 'number', 'min' => 1, 'max' => 3],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['user_id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    /**
     * Finds user by username
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->user_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return false;
    }

    /**
     * Validates password
     */
    public function validatePassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * Get build panel as array
     */
    public function getBuildPanelArray()
    {
        if (empty($this->build_panel)) {
            return array_fill(0, 10, null);
        }
        $panel = json_decode($this->build_panel, true);
        if (!is_array($panel)) {
            return array_fill(0, 10, null);
        }
        // Ensure 10 slots
        while (count($panel) < 10) {
            $panel[] = null;
        }
        return array_slice($panel, 0, 10);
    }

    /**
     * Set build panel from array
     */
    public function setBuildPanelArray(array $panel)
    {
        // Ensure 10 slots
        while (count($panel) < 10) {
            $panel[] = null;
        }
        $this->build_panel = json_encode(array_slice($panel, 0, 10));
    }
}
