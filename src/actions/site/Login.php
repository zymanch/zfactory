<?php

namespace actions\site;

use actions\Base;
use models\User;
use Yii;

/**
 * Quick login as player1 (user_id=1)
 */
class Login extends Base
{
    public function run()
    {
        $user = User::findOne(1);
        if ($user) {
            Yii::$app->user->login($user, 3600 * 24 * 30); // 30 days
            return $this->redirect(['game/index']);
        }

        return $this->redirect(['site/index']);
    }
}
