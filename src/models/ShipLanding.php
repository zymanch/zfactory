<?php
namespace models;

use Yii;
use yii\db\ActiveRecord;

/**
 * ShipLanding ActiveRecord model
 *
 * @property int $ship_landing_id
 * @property int $user_id
 * @property int $landing_id
 * @property int $x Ship-relative X coordinate (offset from ship_attach_x)
 * @property int $y Ship-relative Y coordinate (offset from ship_attach_y)
 * @property int $variation
 *
 * @property User $user
 * @property Landing $landing
 */
class ShipLanding extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%ship_landing}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'landing_id', 'x', 'y'], 'required'],
            [['user_id', 'landing_id', 'x', 'y', 'variation'], 'integer'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'user_id']],
            [['landing_id'], 'exist', 'skipOnError' => true, 'targetClass' => Landing::class, 'targetAttribute' => ['landing_id' => 'landing_id']],
        ];
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['user_id' => 'user_id']);
    }

    /**
     * Gets query for [[Landing]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getLanding()
    {
        return $this->hasOne(Landing::class, ['landing_id' => 'landing_id']);
    }
}
