<?php

namespace commands\actions\site;

use commands\actions\Base;
use Yii;

/**
 * Logout
 */
class Logout extends Base
{
    public function run()
    {
        Yii::$app->user->logout();
        return $this->redirect(['site/index']);
    }
}
